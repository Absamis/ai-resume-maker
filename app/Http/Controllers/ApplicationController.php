<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\Application;
use Barryvdh\Snappy\Facades\SnappyPdf as PDF;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Html;

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

        // Log::info("Prompt generated", [$prompt]);

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
        $cleanJson = trim($html, "` \n\r\t");
        $cleanJson = preg_replace('/^json/', '', $cleanJson); 
        $application->update([
            "body" => $cleanJson,
            "meta" => ["status" => "ready"]
        ]);
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
2. A JSON format to derive from the contents provided.  

Your task: 
- Analyse and polish the resume content.
- Use the analysed content to bring out output in the JSON format i provided.
- Do all you can to make sure information that are array as lot of items in them
- Leave any data you can't find empty or suggest the data that can be in it
- For the job application section, check if there's any content that shows job application otherwise imagine yourself applying to a role that fits the resume
- the cover_letter should be 2000-2500 words. you can group them in 2-3 paragraphs.
- the professional_exerience date format should be in mm/yyyy
- i would love it if you won't leave any data empty, just suggest/predict what could be in it
- all content should be in german
- Output the JSOn as it is, don't interfere with it
Target role: {$role}

SOURCE_RESUME:
{$sourceText}

JSON:

{
    "lastname":"",
    "firstname": "",
    "middlename": "",
    "address": "",
    "place_of_residence": "",
    "email": "",
    "place_of_birth": "",
    "phone_number": "",
    "date_of_birth": "",
    "nationality": "",
    "martial_status": "",
    "short_bio": "",
    "job_application": {
        "job_title": "",
        "company_name": "",
        "company_location": "",
        "date": "",
        "company_zipcode": "",
        "employer_name": "",
        "cover_letter": ""
    },
    "expertises": [],
    "professional_experience": [
        {
            "start_date": "",
            "end_date": "",
            "position": "",
            "job_title": "",
            "company": "",
            "company_location": "",
            "achievements": []
        }
    ],
    "skills": [],
    "linkedin_link": "",
    "professional_summary": "",
    "soft_skills": [],
    "professional_skills": [],
    "languages": [
        {
            "language":"",
            "proficiency_level": ""
        }
    ],
    "certifications": [],
    "education": [
        {
            "start_date": "",
            "end_date": "",
            "degree_held": "",
            "courses_studied": []
        }
    ],
    "interests": [],
    "other_hobbies": []
}
Now produce only the final JSON.
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
        $body = $application->body;
        $data = json_decode($body, true);
        return view('application.pdf', ["data" => $data]);
    }

    

    public function pdf(Application $application)
    {
        $this->authorize('view', $application);
        // Render current HTML body to PDF on-the-fly for download
        $body = $application->body ?? '';
        $rsData = json_decode($body, true);
        try {
            $pdf = PDF::loadView('application.pdf', ["data" => $rsData])
                   ->setPaper('a4')
                    ->setOption('margin-top', 15)
                    ->setOption('margin-bottom', 15)
                    ->setOption('margin-left', 15)
                    ->setOption('margin-right', 15)
                    ->setOption('enable-javascript', true)
                    ->setOption('javascript-delay', 1000) // wait for assets
                    ->setOption('no-stop-slow-scripts', true)
                    ->setOption('enable-smart-shrinking', true);
            return $pdf->download("ai-resume.pdf");
        } catch (\Throwable $e) {
            Log::error('PDF generation failed: '.$e->getMessage());
            $pdfFilename = null;
        }
    }

    public function docx(Application $application)
    {
        $this->authorize('view', $application);
        // Render current HTML body to PDF on-the-fly for download
        $body = $application->body ?? '';
        $rsData = json_decode($body, true);
        try {

            $phpWord = new PhpWord();

            $section = $phpWord->addSection();

            // Render Blade to HTML
            $data = json_decode($body, true);
            $html = view('application.docx', ["data" => $data])->render();

            Html::addHtml($section, $html, false, false);

            // Save DOCX
            $fileName = 'resume-'.time().'.docx';
            $filePath = storage_path("app/public/resumes/{$fileName}");
            $writer = IOFactory::createWriter($phpWord, 'Word2007');
            $writer->save($filePath);
            return response()->download($filePath);//->deleteFileAfterSend(true);
            
        } catch (\Throwable $e) {
            // dd("HTML broke:", $html, $e->getMessage());
            Log::error('DOCS generation failed: '.$e->getMessage());
            $pdfFilename = null;
            return back()->with("error", "Operation failed. Try again");
        }
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
