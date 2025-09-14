<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Mail\GeneratedApplication;
use App\Models\Application;
use Illuminate\Support\Facades\Auth;
use App\Jobs\GenerateApplication;
use Dompdf\Dompdf;
use Dompdf\Options;
use Barryvdh\DomPDF\Facade\Pdf;

class ApplicationController extends Controller
{
    // Track the last assistants call context for file retrieval
    protected ?string $lastThreadId = null;
    protected ?string $lastRunId = null;
    public function create()
    {
        return view('application.form');
    }

    public function store(Request $request)
    {

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'images.*' => ['nullable', 'image', 'max:4096'],
            'files.*' => ['nullable', 'file', 'max:8192'],
            'agree' => ['accepted'],
        ]);

        $storedImages = [];
        $storedFiles = [];

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                if ($image->isValid()) {
                    $storedImages[] = $image->store('uploads/images', 'public');
                }
            }
        }

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                if ($file->isValid()) {
                    $storedFiles[] = $file->store('uploads/files', 'public');
                }
            }
        }

        // Auth user for logging and ownership
        $user = Auth::user();

        // Build public URLs so AI can access uploads if needed (no local parsing)
        $fileUrls = [];
        foreach ($storedFiles as $p) {
            $fileUrls[] = Storage::disk('public')->url($p);
        }
        $imageUrls = [];
        foreach ($storedImages as $p) {
            $imageUrls[] = Storage::disk('public')->url($p);
        }

        // Create application record in processing state; generation handled by queued job
        $application = Application::create([
            'user_id' => $user?->id,
            'email' => $validated['email'],
            'name' => $validated['name'],
            'notes' => $validated['notes'] ?? null,
            'body' => '',
            'txt_path' => null,
            'docx_path' => null,
            'amount_cents' => (int) (config('billing.price_cents') ?? 0),
            'meta' => [
                'images' => $storedImages,
                'files' => $storedFiles,
                'pdf_rel' => null,
                'status' => 'processing',
            ],
        ]);

        
        // $request->validate([
        //     'resume_text'  => 'nullable|string',
        //     'resume_files' => 'nullable|array',
        //     'resume_files.*' => 'file|mimes:pdf,doc,docx,txt|max:15360',
        //     'resume_images'=> 'nullable|array',
        //     'resume_images.*' => 'image|mimes:jpg,jpeg,png|max:10240',
        //     'role' => 'nullable|string|max:255'
        // ]);

        $apiKey = env('OPENAI_API_KEY');
        $model  = env('OPENAI_MODEL', 'gpt-4.1'); // change if needed

        if (!$apiKey) {
            return back()->with('error', 'OpenAI API key not configured.');
        }

        // Step 1: Extract source text (best-effort)
        $sourceText = $request->input('notes', '');
        $sourceType = 'text';
        $sourceName = 'pasted_text';

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $extracted = $this->extractTextFromFile($file);
                $sourceText .= "\n\n".$extracted;
            }
            Log::info("Files extracted", []);
            $sourceType = 'files';
            $sourceName = implode(', ', array_map(fn($f) => $f->getClientOriginalName(), $request->file('files')));
        }

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $ocr = $this->ocrImage($image);
                $sourceText .= $ocr ? ("\n\n".$ocr) : '';
            }
            $sourceType = 'images';
            $sourceName = implode(', ', array_map(fn($f) => $f->getClientOriginalName(), $request->file('images')));
        }

        if (trim($sourceText) === '') {
            return back()->with('error', 'No resume content found. Paste text, or upload a file/image.');
        }

        // Step 2: Build PDF-friendly prompt
        $role = $request->input('role', 'General');
        $prompt = $this->buildPdfFriendlyPrompt($role, $sourceText);

        Log::info("Prompt generated", [$prompt]);

        // Step 3: Call OpenAI Chat Completions (raw HTTP)
        try {
            $resp = Http::withToken($apiKey)
                ->timeout(120)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are an AI that extracts and formats resumes into PDF-friendly HTML.' ],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.2,
                    'max_tokens' => 4000
                ]);
        } catch (\Throwable $e) {
            Log::error('OpenAI request failed: '.$e->getMessage());
            return back()->with('error', 'OpenAI request failed: '.$e->getMessage());
        }

        if (!$resp->successful()) {
            Log::error('OpenAI responded with error', ['status'=>$resp->status(), 'body'=>$resp->body()]);
            return back()->with('error', 'OpenAI error: '.$resp->status());
        }

        $json = $resp->json();
        $html = $json['choices'][0]['message']['content'] ?? null;
        if (!is_string($html) || trim($html) === '') {
            Log::error('OpenAI returned empty HTML', ['resp' => $json]);
            return back()->with('error', 'OpenAI returned no HTML content.');
        }

        // Step 4: Sanitize / clean the HTML (use HTMLPurifier if available)
        $cleanHtml = $html;// $this->sanitizeHtml($html);
        // $cleanHtml = trim($cleanHtml, "```");
        // Step 5: Save HTML and generate PDF
        $basename = 'resume_'.time().'_'.uniqid();
        $pdfFilename  = "resumes/{$basename}.pdf";

        $application->update([
            "body" => $pdfFilename,
            "meta" => ["status" => "ready"]
        ]);

        // Generate PDF using DomPDF
        try {
            $pdf = Pdf::loadHTML($cleanHtml)->setPaper('a4')->setWarnings(false);
            Storage::disk("public")->put($pdfFilename, $pdf->output());
        } catch (\Throwable $e) {
            Log::error('PDF generation failed: '.$e->getMessage());
            // still save HTML and continue (return HTML download)
            $pdfFilename = null;
        }
        return to_route('applications.index');
    }

    protected function extractTextFromFile($file): string
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $text = '';

        try {
            $path = $file->getRealPath();
            if ($ext === 'pdf' && class_exists(\Smalot\PdfParser\Parser::class)) {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($path);
                $text = $pdf->getText();
            } elseif (in_array($ext, ['doc','docx']) && class_exists(\PhpOffice\PhpWord\IOFactory::class)) {
                $phpWord = \PhpOffice\PhpWord\IOFactory::load($path);
                $str = '';
                foreach ($phpWord->getSections() as $section) {
                    foreach ($section->getElements() as $element) {
                        if (method_exists($element, 'getText')) {
                            $str .= $element->getText() . "\n";
                        }
                    }
                }
                $text = $str;
            } else {
                // fallback for plain text or unknown extensions
                $text = file_get_contents($path);
            }
        } catch (\Throwable $e) {
            Log::warning('Local extraction failed: '.$e->getMessage());
            // leave text empty — the prompt still contains the raw file name info later
        }

        return $text ?: '';
    }

    protected function ocrImage($image): ?string
    {
        // Attempt to run Tesseract if installed
        $tesseract = env('TESSERACT_CMD', '/usr/bin/tesseract');
        if (!file_exists($tesseract)) {
            // try which
            $which = trim(shell_exec('which tesseract 2>/dev/null'));
            if ($which) $tesseract = $which;
        }

        if (!file_exists($tesseract)) {
            Log::warning('Tesseract not found; OCR skipped.');
            return null;
        }

        // Save image to temp
        $tmp = tempnam(sys_get_temp_dir(), 'ocr_');
        $image->move(dirname($tmp), basename($tmp));
        $tmpPath = dirname($tmp) . '/' . basename($tmp);

        try {
            // tesseract output to stdout
            $cmd = escapeshellcmd($tesseract) . ' ' . escapeshellarg($tmpPath) . ' stdout';
            $output = shell_exec($cmd);
            return $output ?: null;
        } catch (\Throwable $e) {
            Log::warning('OCR failed: '.$e->getMessage());
            return null;
        } finally {
            // cleanup
            if (file_exists($tmpPath)) @unlink($tmpPath);
        }
    }

    protected function buildPdfFriendlyPrompt(string $role, string $sourceText): string
    {
        $prompt = <<<PROMPT
You are a professional resume formatting assistant.  

I will provide:
1. Resume content (text extracted from file or image).  
2. An HTML template with placeholders.  

Your task: 
- Analyse and polish the resume content.  
- Replace the placeholders with refined resume content.  
- Keep the ENTIRE HTML exactly as provided, including the <html>, <head>, <style>, and <body> tags.  
- Do not strip out or modify any CSS styles.  
- Return the completed HTML as-is, ready to be converted into a PDF. 
Target role: {$role}

SOURCE_RESUME:
{$sourceText}

HTML TEMPLATE:
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="" xml:lang="">
<head>
<title>static/downloads/acee506b-0671-4de3-8924-c3edc9770f24/Vorlage-Zander-Rohan-html.html</title>

<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
 <br/>
<style type="text/css">
<!--
	p {margin: 0; padding: 0;}	.ft10{font-size:54px;font-family:BCDEEE+Calibri;color:#ffffff;}
	.ft11{font-size:17px;font-family:BCDEEE+Calibri;color:#000000;}
	.ft12{font-size:24px;font-family:BCDEEE+Calibri;color:#ffffff;}
	.ft13{font-size:18px;font-family:BCDEEE+Calibri;color:#ffffff;}
	.ft14{font-size:33px;font-family:BCDFEE+Montserrat;color:#58687c;}
	.ft15{font-size:21px;font-family:BCDEEE+Calibri;color:#3b3b39;}
	.ft16{font-size:21px;line-height:46px;font-family:BCDEEE+Calibri;color:#3b3b39;}
-->
</style>
</head>
<body bgcolor="#A0A0A0" vlink="blue" link="blue">
<div id="page1-div" style="position:relative;width:892px;height:1262px;">
<img width="892" height="1262" src="https://absamtech.online/ai-bg.png" />
<p style="position:absolute;top:694px;left:39px;white-space:nowrap" class="ft10">Bewerbungsunterlagen&#160;</p>
<p style="position:absolute;top:3px;left:0px;white-space:nowrap" class="ft11">&#160;</p>
<p style="position:absolute;top:41px;left:0px;white-space:nowrap" class="ft11">&#160;</p>
<p style="position:absolute;top:41px;left:216px;white-space:nowrap" class="ft11">&#160;</p>
<p style="position:absolute;top:121px;left:838px;white-space:nowrap" class="ft12">2025&#160;</p>
<p style="position:absolute;top:1001px;left:40px;white-space:nowrap" class="ft16">V&#160;O&#160;R&#160;N&#160;A&#160;M&#160;E&#160;&#160;&#160;N&#160;A&#160;C&#160;H&#160;N&#160;A&#160;M&#160;E&#160;&#160;<br/>TONIS&#160;BEWERBUNGSHILFE&#160;</p>
</div>
</body>
</html>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>{{name}} — Revamped CV</title>
<style>
  :root{--accent:#58687c;--muted:#3b3b39;--bg:#f4f6f8;--card:#ffffff;}
  body{font-family: Arial, Helvetica, sans-serif; background:var(--bg); color:var(--muted); margin:0; padding:30px;}
  .container{max-width:900px;margin:0 auto;background:var(--card);box-shadow:0 8px 30px rgba(0,0,0,0.08);display:flex;overflow:hidden;}
  .left{width:260px;background:#fbfcfd;padding:28px;border-right:1px solid #eee;}
  .right{flex:1;padding:32px;}
  .name{font-size:24px;color:#1f2937;font-weight:700;margin-bottom:6px;}
  .title{color:var(--accent);font-size:14px;margin-bottom:16px;}
  .contact{font-size:12px;color:#555;margin-bottom:18px;line-height:1.4;}
  h3.section{font-size:13px;color:var(--accent);letter-spacing:2px;margin:18px 0 8px;}
  .muted{font-size:12px;color:#58687c;margin-bottom:6px;}
  .list{font-size:13px;line-height:1.45;margin:6px 0 0;}
  .skill{background:#eef2f6;padding:6px 8px;border-radius:6px;display:inline-block;margin:4px 6px 6px 0;font-size:12px;color:#274156;}
  .exp{margin-bottom:12px;}
  .exp .role{font-weight:700;color:#17202a;}
  .exp .meta{font-size:12px;color:#6b7280;margin-bottom:6px;}
  .bullet{margin-left:12px;margin-bottom:6px;font-size:13px;color:#394042;}
  .edu, .project{margin-bottom:10px;}
  .footer-note{font-size:11px;color:#6b7280;margin-top:18px;}
  @media(max-width:880px){.container{flex-direction:column}.left{width:100%;border-right:none;border-bottom:1px solid #eee}}
</style>
</head>
<body>
<div class="container" role="main" aria-label="Curriculum Vitae">
  <aside class="left" aria-label="Quick info">
    <div class="name">{{name}}</div>
    <div class="title">{{title}}</div>
    <div class="contact">
      {{location}}<br/>
      Email: {{email}}<br/>
      Phone: {{phone}}
    </div>

    <h3 class="section">LANGUAGES</h3>
    {{languages}}

    <h3 class="section">CERTIFICATIONS</h3>
    {{certifications}}

    <h3 class="section">SKILLS</h3>
    <div style="margin-top:8px;">
      {{skills}}
    </div>

    <h3 class="section">INTERESTS</h3>
    {{interests}}
  </aside>

  <section class="right" aria-label="Main content">
    <div role="region" aria-label="Profile summary">
      <p class="muted"><strong>PROFILE</strong></p>
      <p style="font-size:14px;color:#273036;line-height:1.5;margin-top:6px;">
        {{profile}}
      </p>
    </div>

    <h3 class="section" style="margin-top:18px;">WORK EXPERIENCE</h3>
    {{experience}}

    <h3 class="section" style="margin-top:10px;">EDUCATION</h3>
    {{education}}

    <h3 class="section">PROJECTS & ACHIEVEMENTS</h3>
    {{projects}}

    <p class="footer-note">{{footer}}</p>
  </section>
</div>
</body>
</html>



Now produce only the final HTML.
PROMPT;
        return $prompt;
    }

    protected function sanitizeHtml(string $html): string
    {
        // Preferably use HTMLPurifier (ezyang/htmlpurifier)
        if (class_exists(\HTMLPurifier::class)) {
            $config = \HTMLPurifier_Config::createDefault();
            $purifier = new \HTMLPurifier($config);
            return $purifier->purify($html);
        }

        // Basic fallback - remove <script> and event attributes
        // remove script/style blocks
        $html = preg_replace('#<script.*?>.*?</script>#is', '', $html);
        $html = preg_replace('#<style.*?>.*?</style>#is', '', $html);
        // remove on* attributes (onclick etc)
        $html = preg_replace('/ on\w+="[^"]*"/i', '', $html);
        $html = preg_replace("/ on\w+='[^']*'/i", '', $html);

        return $html;
    }

    public function storex(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'images.*' => ['nullable', 'image', 'max:4096'],
            'files.*' => ['nullable', 'file', 'max:8192'],
            'agree' => ['accepted'],
        ]);

        $storedImages = [];
        $storedFiles = [];

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                if ($image->isValid()) {
                    $storedImages[] = $image->store('uploads/images', 'public');
                }
            }
        }

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                if ($file->isValid()) {
                    $storedFiles[] = $file->store('uploads/files', 'public');
                }
            }
        }

        // Auth user for logging and ownership
        $user = Auth::user();

        // Build public URLs so AI can access uploads if needed (no local parsing)
        $fileUrls = [];
        foreach ($storedFiles as $p) {
            $fileUrls[] = Storage::disk('public')->url($p);
        }
        $imageUrls = [];
        foreach ($storedImages as $p) {
            $imageUrls[] = Storage::disk('public')->url($p);
        }

       

        // Create application record in processing state; generation handled by queued job
        $application = Application::create([
            'user_id' => $user?->id,
            'email' => $validated['email'],
            'name' => $validated['name'],
            'notes' => $validated['notes'] ?? null,
            'body' => '',
            'txt_path' => null,
            'docx_path' => null,
            'amount_cents' => (int) (config('billing.price_cents') ?? 0),
            'meta' => [
                'images' => $storedImages,
                'files' => $storedFiles,
                'pdf_rel' => null,
                'status' => 'processing',
            ],
        ]);

        // Process immediately (no queue). Keep status logic in place; UI will reflect updates.
        try {
            // (new GenerateApplication($application->id))->handle();
            Log::info('GenerateApplication processed synchronously', ['application_id' => $application->id]);
        } catch (\Throwable $e) {
            Log::error('Synchronous generation error', ['application_id' => $application->id, 'message' => $e->getMessage()]);
        }

        return to_route('applications.index');
    }

    // Authenticated: list and download
    public function index(Request $request)
    {
        $user = Auth::user();
        $apps = Application::query()
            ->when($user, fn($q) => $q->where('user_id', $user->id))
            ->latest()
            ->paginate(10);

        return view('application.index', [
            'applications' => $apps,
        ]);
    }

    public function download(Request $request, Application $application, string $type)
    {
        $this->authorize('view', $application);

        $path = null;
        $filename = null;
        if ($type === 'docx' && $application->docx_path) {
            $path = $application->docx_path;
            $filename = 'application.docx';
        } elseif ($type === 'pdf') {
            if($application->body){
                $path = Storage::disk('public')->path($application->body);
                $filename = 'application.pdf';
            }
            // $pdfRel = data_get($application->meta, 'pdf_rel');
            // if ($pdfRel) {
            //     $abs = Storage::disk('public')->path($pdfRel);
            //     if (is_file($abs)) {
            //         $path = $abs;
            //         $filename = 'application.pdf';
            //     }
            // }
        } else {
            abort(404);
        }

        return response()->download($path, $filename);
    }

    public function preview(Application $application)
    {
        $this->authorize('view', $application);
        $name = $application->name;
        $date = now()->format('Y-m-d');
        // Always prefer rendering the exact multi-page HTML template if present
        $tpl = base_path('doc/Vorlage-Zander-Rohan-html.html');
        if (is_file($tpl)) {
            $body = @file_get_contents($tpl) ?: '';
        } else {
            $body = $application->body ?: '<p>No content available.</p>';
        }
        return view('application.preview', compact('name', 'date', 'body', 'application'));
    }

    public function pdf(Application $application)
    {
        $this->authorize('view', $application);
        // Render current HTML body to PDF on-the-fly for download
        $body = $application->body ?? '';
        return Storage::disk("public")->download($body, "application.pdf");

        // dd($path);
        // if (stripos($body, '<html') !== false) {
        //     $html = $body;
        // } else {
        //     $html = view('application.pdf', [
        //         'name' => $application->name,
        //         'date' => now()->format('Y-m-d'),
        //         'body' => $body,
        //         'headerBg' => data_get($application->meta, 'header_bg_data'),
        //     ])->render();
        // }

        // $options = new Options();
        // // Allow remote assets (e.g., header background images) when rendering PDFs
        // $options->set('isRemoteEnabled', true);
        // $options->set('isHtml5ParserEnabled', true);
        // $dompdf = new Dompdf($options);
        // $dompdf->loadHtml($html);
        // $dompdf->setPaper('A4');
        // $dompdf->render();
        // return response($path, 200, [
        //     'Content-Type' => 'application/pdf',
        //     'Content-Disposition' => 'attachment; filename="application.pdf"',
        // ]);
    }

    public function destroy(Application $application)
    {
        try {
            $apiKey = config('services.openai.key') ?? env('OPENAI_API_KEY') ?? env('OPEN_API_KEY');
            $threadId = data_get($application->meta, 'openai.thread_id');
            $fileIds = (array) data_get($application->meta, 'openai.file_ids', []);
            if ($apiKey && $threadId) {
                Http::timeout(15)
                    ->withToken($apiKey)
                    ->withHeaders(['OpenAI-Beta' => 'assistants=v2'])
                    ->delete("https://api.openai.com/v1/threads/{$threadId}");
            }
            if ($apiKey && !empty($fileIds)) {
                foreach ($fileIds as $fid) {
                    Http::timeout(15)
                        ->withToken($apiKey)
                        ->delete("https://api.openai.com/v1/files/{$fid}");
                }
            }
        } catch (\Throwable $e) {
            Log::warning('OpenAI cleanup on delete failed', ['id' => $application->id, 'message' => $e->getMessage()]);
        }

        // Delete stored files
        try {
            $pdfRel = data_get($application->meta, 'pdf_rel');
            if ($pdfRel) {
                Storage::disk('public')->delete($pdfRel);
            }
            if ($application->docx_path && is_file($application->docx_path)) {
                @unlink($application->docx_path);
            }
        } catch (\Throwable $e) {
            // ignore
        }

        $application->delete();
        return to_route('applications.index')->with('status', 'Application deleted');
    }
}
