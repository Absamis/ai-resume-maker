<?php

namespace App\Jobs;

use App\Mail\GeneratedApplication;
use App\Models\Application;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Dompdf\Dompdf;
use Dompdf\Options;

class GenerateApplication implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $applicationId;

    public function __construct(int $applicationId)
    {
        $this->applicationId = $applicationId;
        $this->onQueue('default');
    }

    /**
     * If the assistant didn't return our A4 template structure, wrap the content into it.
     * Leaves placeholders for unknown fields. Keeps content body intact.
     */
    protected function coerceToTemplate(string $html, string $candidateName): string
    {
        $trim = trim($html);
        // Heuristic: if it already contains our template markers, keep as-is
        $hasTemplate = (str_contains($trim, 'class="page"') || str_contains($trim, 'class="header-inner"') || str_contains($trim, 'Signature whitespace'));
        if ($hasTemplate) { return $html; }

        // Extract inner body content if a full HTML document
        $bodyInner = $trim;
        if (preg_match('/<body[^>]*>([\s\S]*?)<\/body>/i', $trim, $m)) {
            $bodyInner = trim($m[1]);
        }

        // Build template and inject body
        $date = now()->format('Y-m-d');
        $template = <<<HTML
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    @page { size: A4; margin: 20mm; }
    html, body { height: 100%; }
    body { margin: 0; font-family: Arial, sans-serif; font-size: 14px; color: #111; line-height: 1.55; }
    .page { width: 170mm; min-height: 257mm; margin: 0 auto; }
    .header { height: 40mm; border-radius: 6px; background: linear-gradient(135deg,#1f2937 0%,#111827 60%,#0b1220 100%); position: relative; margin-bottom: 10mm; }
    .header-inner { position:absolute; inset: 0; padding: 10mm; color: #fff; }
    .header-title { font-size: 16px; font-weight: 700; margin: 0; }
    .header-sub { font-size: 12px; opacity: .9; margin-top: 2mm; }
    .meta { color:#555; font-size:11px; margin-bottom: 4mm; }
    .subject { font-weight:700; font-size:16px; margin:0 0 12px 0; }
    .content p { margin: 0 0 10px; }
    .signature { margin-top: 10mm; padding-top: 6mm; border-top: 1px solid #e5e7eb; }
    .signature-space { height: 18mm; display:block; }
  </style>
  <title>Cover Letter — {{CANDIDATE_NAME}}</title>
  </head>
<body>
  <div class="page">
    <div class="header">
      <div class="header-inner">
        <h1 class="header-title">{{CANDIDATE_NAME}}</h1>
        <div class="header-sub">{{DATE}}</div>
      </div>
    </div>

    <div class="meta">{{CONTACT_BLOCK}}</div>

    <div class="content">
      <div>{{RECIPIENT_BLOCK}}</div>
      <div class="subject">{{SUBJECT_LINE}}</div>
      {{BODY_HTML}}
    </div>

    <div class="signature">
      <span class="signature-space"></span>
      <div><strong>{{CANDIDATE_NAME}}</strong></div>
      <div>{{CONTACT_LINES}}</div>
    </div>
  </div>
</body>
</html>
HTML;

        $out = str_replace(
            ['{{CANDIDATE_NAME}}', '{{DATE}}', '{{BODY_HTML}}'],
            [e($candidateName), e($date), $bodyInner],
            $template
        );
        // Leave other placeholders intact per requirement
        return $out;
    }

    protected bool $debug = false;
    public int $timeout = 300; // seconds, worker will kill if longer
    public int $tries = 3;     // retry a few times on failure
    public int $backoff = 30;  // seconds between retries

    public function handle(): void
    {
        $this->debug = (bool) (env('AI_DEBUG_LOG') ?? false);
        $application = Application::find($this->applicationId);
        if (!$application) {
            Log::error('GenerateApplication: Application not found', ['id' => $this->applicationId]);
            return;
        }

        $meta = $application->meta ?? [];
        $storedFiles = $meta['files'] ?? [];
        $storedImages = $meta['images'] ?? [];

        $name = $application->name;
        $email = $application->email;
        $notes = $application->notes ?? '';

        // Mark processing
        $this->updateMeta($application, ['status' => 'processing']);

        $fileUrls = array_map(fn($p) => Storage::disk('public')->url($p), $storedFiles);
        $imageUrls = array_map(fn($p) => Storage::disk('public')->url($p), $storedImages);

        $prompt = $this->buildPrompt($name, $notes, count($storedImages), array_map(fn($p) => basename($p), $storedFiles), $fileUrls, $imageUrls);
        $this->dlog('Prompt built', [
            'chars' => strlen($prompt),
            'preview' => mb_substr($prompt, 0, 1000),
        ]);

        Log::info('GenerateApplication: OpenAI request starting (job)', [
            'app_id' => $application->id,
            'model' => 'gpt-4o-mini',
            'prompt_chars' => strlen($prompt),
        ]);

        // Upload only user-provided files (resume + job image). Do not upload templates.
        $fileIds = [];
        try {
            $fileIds = array_merge(
                $this->uploadRelativeFilesToOpenAI($storedFiles),
                $this->uploadRelativeFilesToOpenAI($storedImages)
            );
            Log::info('GenerateApplication: OpenAI files uploaded', ['count' => count($fileIds)]);
            $this->dlog('Uploaded files to OpenAI', [
                'files_count' => count($storedFiles),
                'images_count' => count($storedImages),
                'all_file_ids' => $fileIds,
            ]);
        } catch (\Throwable $e) {
            Log::warning('GenerateApplication: OpenAI file upload failed', ['message' => $e->getMessage()]);
        }

        // Run assistant
        $resultText = $this->generateWithAssistant($prompt, $fileIds);
        // Persist OpenAI context for cleanup
        if ($this->lastThreadId) {
            $this->updateMeta($application, ['openai.thread_id' => $this->lastThreadId]);
        }

        if (!$resultText) {
            Log::warning('GenerateApplication: Assistant returned no text, using fallback');
            $resultText = $this->fallbackLetter($name, $notes);
        }
        // Ensure we have valid HTML (assistant may return plain text)
        // $resultText = $this->ensureHtml($resultText, $name);
        // // Coerce into our A4 template with header/signature if AI did not follow the template
        // $resultText = $this->coerceToTemplate($resultText, $name);

        // Prepare optional header background (first uploaded image -> base64 data URI)
        $headerDataUri = $this->makeHeaderDataUri($storedImages);

        // Inject known placeholders: candidate info, contact lines, and header background where applicable
        // $resultText = $this->injectKnownPlaceholders(
        //     html: $resultText,
        //     candidateName: $name,
        //     candidateEmail: $email,
        //     headerDataUri: $headerDataUri
        // );

        // HTML-only mode: skip fetching/creating DOCX/PDF artifacts.
        $timestamp = now()->format('Ymd_His');
        $safeSlug = Str::slug($name) ?: 'application';
        $baseDir = "generated/{$safeSlug}_{$timestamp}";
        $docxPath = null;
        $pdfPath = null;

        // Update application
        Log::info("Result payload", [$resultText]);

        $body = $resultText ?? '';
        if (stripos($body, '<html') !== false) {
            // Already a full HTML document from the assistant; render as-is
            $html = $body;
        } else {
            $html = view('application.pdf', [
                'name' => $application->name,
                'date' => now()->format('Y-m-d'),
                'body' => $body,
                'headerBg' => data_get($application->meta, 'header_bg_data'),
            ])->render();
        }

        $options = new Options();
        // Allow remote assets (e.g., header background images) when rendering PDFs
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4');
        $dompdf->render();
        $output = $dompdf->output();
        $filePath = storage_path('app/public/'.time().'.pdf');// 'path/to/save/your_document.pdf'; // Specify your desired file path
        file_put_contents($filePath, $output);
        // return response($dompdf->output(), 200, [
        //     'Content-Type' => 'application/pdf',
        //     'Content-Disposition' => 'attachment; filename="application.pdf"',
        // ]);
        $pdf = Pdf::loadHTML($resultText);

        // Store PDF in storage/app/public/resumes/
        $fileName = 'resume_' . time() . '.pdf';
        $url = Storage::disk('public')->put('resumes/' . $fileName, $pdf->output());
        $application->body = $url; // HTML content expected
        $application->docx_path = null; // no docx in HTML-only mode
        $this->updateMeta($application, [ 'pdf_rel' => null, 'status' => 'ready', 'header_bg_data' => $headerDataUri ]);
        $application->save();

        // Email
        try {
            $mailable = new GeneratedApplication(
                name: $name,
                body: $resultText,
                docxPath: null,
                pdfPath: null,
            );
            $this->dlog('Prepared email (HTML-only)', [
                'to' => $email,
                'has_docx' => false,
                'has_pdf' => false,
            ]);
            Mail::to($email)->send($mailable);
            Log::info('GenerateApplication: Email sent from job (HTML-only)', ['app_id' => $application->id]);
        } catch (\Throwable $e) {
            Log::error('GenerateApplication: Mail send failed', ['message' => $e->getMessage()]);
        }
    }

    // ---- Helpers (duplicated minimal versions for job) ----

    protected ?string $lastThreadId = null;
    protected ?string $lastRunId = null;

    /**
     * Merge changes into the application's meta array and persist in-memory
     */
    protected function updateMeta(Application $application, array $changes): void
    {
        try {
            $meta = $application->meta ?? [];
            foreach ($changes as $k => $v) {
                data_set($meta, $k, $v);
            }
            $application->meta = $meta;
        } catch (\Throwable $e) {
            Log::warning('GenerateApplication: updateMeta failed', ['error' => $e->getMessage()]);
        }
    }

    protected function buildPrompt(string $name, string $notes, int $imageCount, array $fileNames, array $fileUrls = [], array $imageUrls = []): string
    {
        $fileList = empty($fileNames) ? 'none' : implode(', ', $fileNames);
        $notes = trim($notes);
        // $urlBlock = '';
        // if (!empty($fileUrls) || !empty($imageUrls)) {
        //     $urlBlock = "\nYou can access the user's uploaded files via these URLs:\n" .
        //         (empty($fileUrls) ? '' : ('Files:\n- '.implode("\n- ", $fileUrls).'\n')) .
        //         (empty($imageUrls) ? '' : ('Images:\n- '.implode("\n- ", $imageUrls).'\n'));
        // }

        return <<<PROMPT

            You are an AI Resume Refiner.

            Input: find the uploaded resume fie is attached

            Task:
            1. Extract all relevant information from the uploaded resume file:
            - Full name
            - Contact details (email, phone, location)
            - Professional summary
            - Skills
            - Work experiences (title, company, start_date, end_date, responsibilities)
            - Education
            - Projects
            - Languages (if any)

            2. Rewrite and revamp the content into clear, concise, recruiter-friendly language.
            - Keep fact-based, do NOT invent new details.
            - Use bullet points for skills and responsibilities.
            - Format experiences in reverse chronological order.

            3. Create a **PDF-friendly HTML resume template**:
            - Use semantic HTML5 structure with inline or embedded CSS.
            - Stick to simple, professional fonts (Arial, Helvetica, Georgia).
            - Use clear section headers and spacing.
            - Avoid background images or complex layouts that may break in PDF.
            - Ensure margins and font sizes are suitable for A4/Letter PDF printing.

            4. Insert the refined content into the generated HTML structure.

            5. Return ONLY the final completed HTML. 
            Do not include explanations, comments, or extra text — just pure HTML that can be directly converted to PDF.

            PROMPT;
    }

    protected function getOrCreateAssistantId(): ?string
    {
        $apiKey = config('services.openai.key') ?? env('OPENAI_API_KEY') ?? env('OPEN_API_KEY');
        if (!$apiKey) { return null; }
        $assistantId = env('OPENAI_ASSISTANT_ID');
        if ($assistantId) { return $assistantId; }

        try {
            $payload = [
                'model' => 'gpt-4o-mini',
                'name' => 'ShinyAdventure HTML Cover Letter Assistant',
                'instructions' => 'You generate concise, personalized job application letters as self-contained HTML using details from user inputs and uploaded files/images. Use file_search to extract factual data (employer, role, technologies, achievements) from the resume and job screenshot. Return only valid HTML with inline CSS. Do not include placeholders or meta commentary.',
                'tools' => [ ['type' => 'file_search'] ],
            ];
            $this->dlog('Creating assistant', ['payload' => $payload]);
            $res = Http::timeout(30)->retry(3, 1000)
                ->withToken($apiKey)
                ->withHeaders(['OpenAI-Beta' => 'assistants=v2'])
                ->acceptJson()
                ->post('https://api.openai.com/v1/assistants', $payload);
            if ($res->successful()) {
                $id = $res->json('id');
                Log::info('OpenAI assistant created (job)', ['assistant_id' => $id]);
                $this->dlog('Assistant create response', ['status' => $res->status(), 'body' => $res->json()]);
                return $id;
            }
            $this->dlog('Assistant create failed', ['status' => $res->status(), 'body' => $res->body()]);
        } catch (\Throwable $e) {
            Log::error('Assistant create failed (job)', ['message' => $e->getMessage()]);
        }
        return null;
    }

    protected function generateWithAssistant(string $prompt, array $fileIds): ?string
    {
        $apiKey = config('services.openai.key') ?? env('OPENAI_API_KEY') ?? env('OPEN_API_KEY');
        if (!$apiKey) { return null; }
        try {
            $this->dlog('Creating thread');
            $threadRes = Http::timeout(30)->retry(3, 1000)
                ->withToken($apiKey)
                ->withHeaders(['OpenAI-Beta' => 'assistants=v2'])
                ->acceptJson()
                ->post('https://api.openai.com/v1/threads', []);
            if (!$threadRes->successful()) { return null; }
            $threadId = $threadRes->json('id');
            $this->lastThreadId = $threadId;
            $this->dlog('Thread created', ['thread_id' => $threadId, 'status' => $threadRes->status(), 'body' => $threadRes->json()]);

            $msgPayload = [ 'role' => 'user', 'content' => $prompt ];
            if (!empty($fileIds)) {
                $attachments = [];
                foreach ($fileIds as $fid) {
                    $attachments[] = ['file_id' => $fid, 'tools' => [['type' => 'file_search']]];
                }
                $msgPayload['attachments'] = $attachments;
            }
            $this->dlog('Posting message', [ 'payload_preview' => [
                'content_chars' => strlen($prompt),
                'attachments' => isset($msgPayload['attachments']) ? count($msgPayload['attachments']) : 0,
            ]]);
            $msgRes = Http::timeout(60)->retry(3, 1000)
                ->withToken($apiKey)
                ->withHeaders(['OpenAI-Beta' => 'assistants=v2'])
                ->acceptJson()
                ->post("https://api.openai.com/v1/threads/{$threadId}/messages", $msgPayload);
            if (!$msgRes->successful()) { return null; }
            $this->dlog('Message posted', ['status' => $msgRes->status(), 'body' => $msgRes->json()]);

            $assistantId = $this->getOrCreateAssistantId();
            if (!$assistantId) { return null; }
            $runPayload = [ 'assistant_id' => $assistantId ];
            if (!empty($fileIds)) { $runPayload['tools'] = [['type' => 'file_search']]; }

            $this->dlog('Starting run', ['payload' => $runPayload]);
            $runRes = Http::timeout(60)->retry(3, 1000)
                ->withToken($apiKey)
                ->withHeaders(['OpenAI-Beta' => 'assistants=v2'])
                ->acceptJson()
                ->post("https://api.openai.com/v1/threads/{$threadId}/runs", $runPayload);
            if (!$runRes->successful()) { return null; }
            $runId = $runRes->json('id');
            $this->lastRunId = $runId;
            $this->dlog('Run started', ['run_id' => $runId, 'status' => $runRes->status(), 'body' => $runRes->json()]);

            $deadline = now()->addMinutes(5);
            while (now()->lt($deadline)) {
                sleep(5);
                $getRun = Http::timeout(30)->retry(3, 1000)
                    ->withToken($apiKey)
                    ->withHeaders(['OpenAI-Beta' => 'assistants=v2'])
                    ->acceptJson()
                    ->get("https://api.openai.com/v1/threads/{$threadId}/runs/{$runId}");
                if (!$getRun->successful()) { continue; }
                $status = $getRun->json('status');
                $this->dlog('Run poll', ['status' => $status, 'step' => $getRun->json()]);
                if (in_array($status, ['completed', 'failed', 'cancelled', 'expired'])) {
                    if ($status !== 'completed') { return null; }
                    break;
                }
            }

            $msgList = Http::timeout(30)->retry(3, 1000)
                ->withToken($apiKey)
                ->withHeaders(['OpenAI-Beta' => 'assistants=v2'])
                ->acceptJson()
                ->get("https://api.openai.com/v1/threads/{$threadId}/messages", ['limit' => 1, 'order' => 'desc']);
            if (!$msgList->successful()) { return null; }
            $messages = $msgList->json('data') ?? [];
            $this->dlog('Fetched messages', ['count' => count($messages)]);
            foreach ($messages as $m) {
                if (($m['role'] ?? '') === 'assistant') {
                    $content = $m['content'][0]['text']['value'] ?? null;
                    $this->dlog('Assistant message', [ 'content_preview' => is_string($content) ? mb_substr($content, 0, 1000) : null ]);
                    if (is_string($content) && trim($content) !== '') { return trim($content); }
                }
            }
        } catch (\Throwable $e) {
            Log::error('Assistants exception (job)', ['message' => $e->getMessage()]);
            $this->dlog('Assistants exception details', ['trace' => $e->getTraceAsString()]);
        }
        return null;
    }

    protected function fetchAssistantOutputFiles(): array
    {
        $apiKey = config('services.openai.key') ?? env('OPENAI_API_KEY') ?? env('OPEN_API_KEY');
        if (!$apiKey || !$this->lastThreadId || !$this->lastRunId) { return []; }
        try {
            $stepsRes = Http::timeout(60)
                ->withToken($apiKey)
                ->withHeaders(['OpenAI-Beta' => 'assistants=v2'])
                ->acceptJson()
                ->get("https://api.openai.com/v1/threads/{$this->lastThreadId}/runs/{$this->lastRunId}/steps", [ 'limit' => 50 ]);
            if (!$stepsRes->successful()) { return []; }
            $out = [];
            $data = $stepsRes->json('data') ?? [];
            $this->dlog('Run steps fetched', ['count' => count($data)]);
            foreach ($data as $step) {
                $details = $step['step_details'] ?? [];
                if (($details['type'] ?? '') === 'tool_calls') {
                    foreach (($details['tool_calls'] ?? []) as $tc) {
                        if (($tc['type'] ?? '') === 'code_interpreter') {
                            foreach (($tc['code_interpreter']['outputs'] ?? []) as $o) {
                                if (($o['type'] ?? '') === 'file_path' && !empty($o['file_id'])) {
                                    $out[] = ['id' => $o['file_id'], 'filename' => $o['file_path']['filename'] ?? null];
                                }
                            }
                        }
                    }
                }
            }
            return $out;
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function getOpenAIFileMeta(string $fileId): array
    {
        $apiKey = config('services.openai.key') ?? env('OPENAI_API_KEY') ?? env('OPEN_API_KEY');
        if (!$apiKey) { return []; }
        try {
            $res = Http::timeout(30)
                ->withToken($apiKey)
                ->acceptJson()
                ->get("https://api.openai.com/v1/files/{$fileId}");
            if ($res->successful()) {
                $json = $res->json() ?: [];
                $this->dlog('OpenAI file meta', ['file_id' => $fileId, 'meta' => $json]);
                return $json;
            }
        } catch (\Throwable $e) { }
        return [];
    }

    protected function downloadOpenAIFileToPublic(string $fileId, string $baseDir, ?string $desiredName = null): ?string
    {
        $apiKey = config('services.openai.key') ?? env('OPENAI_API_KEY') ?? env('OPEN_API_KEY');
        if (!$apiKey) { return null; }
        try {
            $meta = $this->getOpenAIFileMeta($fileId);
            $filename = $desiredName ?: ($meta['filename'] ?? ("openai_{$fileId}"));
            $contentRes = Http::timeout(120)
                ->withToken($apiKey)
                ->withHeaders(['Accept' => 'application/octet-stream'])
                ->get("https://api.openai.com/v1/files/{$fileId}/content");
            if (!$contentRes->successful()) { $this->dlog('Download failed', ['file_id' => $fileId, 'status' => $contentRes->status()]); return null; }
            $relative = rtrim($baseDir, '/').'/'.$filename;
            $bytes = strlen($contentRes->body());
            Storage::disk('public')->put($relative, $contentRes->body());
            $this->dlog('File saved', ['file_id' => $fileId, 'relative' => $relative, 'bytes' => $bytes]);
            return $relative;
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function getCachedTemplateFileIds(): array
    {
        $apiKey = config('services.openai.key') ?? env('OPENAI_API_KEY') ?? env('OPEN_API_KEY');
        if (!$apiKey) { return []; }
        $docxTpl = base_path('doc/Vorlage Zander Rohan.docx');
        $pdfTpl  = base_path('doc/Vorlage Zander Rohan.pdf');
        $existing = array_values(array_filter([$docxTpl, $pdfTpl], fn($p) => is_file($p)));
        if (empty($existing)) { return []; }
        $finger = [];
        foreach ($existing as $p) { $finger[] = basename($p) . '|' . filesize($p) . '|' . filemtime($p); }
        $key = 'openai_template_file_ids_' . sha1(implode(';', $finger));
        return Cache::remember($key, now()->addHours(6), function () use ($existing) {
            $ids = [];
            foreach ($existing as $abs) {
                try {
                    $response = Http::timeout(60)
                        ->withToken(env('OPENAI_API_KEY') ?? env('OPEN_API_KEY'))
                        ->attach('file', file_get_contents($abs), basename($abs))
                        ->asMultipart()
                        ->post('https://api.openai.com/v1/files', [ ['name' => 'purpose', 'contents' => 'assistants'] ]);
                    if ($response->successful()) {
                        $id = $response->json('id'); if ($id) { $ids[] = $id; }
                        $this->dlog('Template uploaded', ['path' => $abs, 'file_id' => $id]);
                    } else {
                        $this->dlog('Template upload failed', ['path' => $abs, 'status' => $response->status(), 'body' => $response->body()]);
                    }
                } catch (\Throwable $e) {}
            }
            return $ids;
        });
    }

    protected function uploadRelativeFilesToOpenAI(array $relativePaths): array
    {
        $apiKey = env('OPENAI_API_KEY') ?? env('OPEN_API_KEY');
        if (!$apiKey) { return []; }
        $ids = [];
        foreach ($relativePaths as $rel) {
            $abs = Storage::disk('public')->path($rel);
            if (!is_file($abs)) { continue; }
            try {
                $response = Http::timeout(60)
                    ->withToken($apiKey)
                    ->attach('file', file_get_contents($abs), basename($abs))
                    ->asMultipart()
                    ->post('https://api.openai.com/v1/files', [ ['name' => 'purpose', 'contents' => 'assistants'] ]);
                if ($response->successful()) { $data = $response->json(); if (!empty($data['id'])) { $ids[] = $data['id']; } $this->dlog('User file uploaded', ['rel' => $rel, 'file_id' => $data['id'] ?? null]); }
            } catch (\Throwable $e) {}
        }
        return $ids;
    }

    protected function generateDocxFallback(string $name, string $email, string $notes, string $body, string $baseDir, string $timestamp): ?string
    {
        try {
            if (class_exists('PhpOffice\\PhpWord\\TemplateProcessor')) {
                $templateCandidates = [ base_path('doc/Vorlage Zander Rohan.docx') ];
                $templatePath = null;
                foreach ($templateCandidates as $cand) { if (is_file($cand)) { $templatePath = $cand; break; } }
                if ($templatePath) {
                    $processor = new \PhpOffice\PhpWord\TemplateProcessor($templatePath);
                    $processor->setValue('name', $name);
                    $processor->setValue('email', $email);
                    $processor->setValue('notes', $notes ?: '-');
                    $processor->setValue('date', now()->format('Y-m-d'));
                    $bodyXml = str_replace(["\r\n", "\n", "\r"], '<w:br/>', $body);
                    $processor->setValue('body', $bodyXml);
                    $tempPath = storage_path("app/tmp_job_{$timestamp}.docx");
                    $processor->saveAs($tempPath);
                    $relative = "$baseDir/application_{$timestamp}.docx";
                    Storage::disk('public')->put($relative, file_get_contents($tempPath));
                    @unlink($tempPath);
                    $this->dlog('DOCX fallback saved', ['relative' => $relative]);
                    return $relative;
                }
            }
        } catch (\Throwable $e) {}
        return null;
    }

    protected function fallbackLetter(string $name, string $notes): string
    {
        $notesLine = $notes ? "\n\nAdditional context: {$notes}" : '';
        return "Dear Hiring Team,\n\nMy name is {$name}. I am excited to express my interest in opportunities that align with my background. I bring a track record of delivering results, collaborating across teams, and continuously improving processes to create impact.".
            "\n\nI would welcome the chance to contribute, learn, and grow while supporting your goals. Please find my details attached or available upon request.\n{$notesLine}\n\nKind regards,\n{$name}";
    }

    protected function ensureHtml(string $content, string $name): string
    {
        $trim = trim($content);
        $looksHtml = str_contains($trim, '<') && str_contains($trim, '>');
        if ($looksHtml) { return $content; }
        $escaped = e($trim);
        return <<<HTML
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: Arial, sans-serif; color:#111; line-height:1.5; font-size:14px; }
    h1 { font-size:20px; margin:0 0 6px; }
    .muted { color:#666; font-size:12px; }
    p { margin:0 0 10px; }
  </style>
  <title>Application - {$name}</title>
  </head>
<body>
  <h1>Application Letter</h1>
  <div class="muted">Generated</div>
  <div>
    <p>{$escaped}</p>
  </div>
</body>
</html>
HTML;
    }

    protected function dlog(string $message, array $context = []): void
    {
        // Always log at info level so it shows up without requiring LOG_LEVEL=debug
        Log::info('[AI TRACE] '.$message, $context);
    }

    /**
     * If user uploaded any images, convert the first one into a data URI suitable
     * for inline CSS background-image. Returns empty string if not available.
     */
    protected function makeHeaderDataUri(array $storedImages): string
    {
        try {
            $first = $storedImages[0] ?? null;
            if (!$first) { return ''; }
            $abs = Storage::disk('public')->path($first);
            if (!is_file($abs)) { return ''; }
            $mime = @mime_content_type($abs) ?: 'image/png';
            $data = base64_encode(file_get_contents($abs));
            return "data:$mime;base64,$data";
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * Replace known placeholders in the template and optionally inject header background
     * using the provided data URI. Leaves unknown placeholders untouched.
     */
    protected function injectKnownPlaceholders(string $html, string $candidateName, string $candidateEmail = '', string $headerDataUri = ''): string
    {
        $out = $html;

        // Candidate name placeholders
        $out = str_replace('{{CANDIDATE_NAME}}', e($candidateName), $out);

        // Contact lines/blocks fallbacks using name + email
        $contactLines = trim(implode(' · ', array_values(array_filter([
            $candidateName ?: null,
            $candidateEmail ?: null,
        ]))));
        $contactBlock = $contactLines;
        if ($contactLines === '') { $contactLines = '{{CONTACT_LINES}}'; $contactBlock = '{{CONTACT_BLOCK}}'; }

        $replacements = [
            '{{CONTACT_LINES}}' => e($contactLines),
            '{{CONTACT_BLOCK}}' => e($contactBlock),
            '{{CANDIDATE_EMAIL}}' => e($candidateEmail ?: '{{CANDIDATE_EMAIL}}'),
            '{{DATE}}' => e(now()->format('Y-m-d')),
        ];
        $out = strtr($out, $replacements);

        // Inject header background if provided and not already present
        if ($headerDataUri) {
            $hasBgImage = stripos($out, 'background-image:') !== false && stripos($out, 'class="header"') !== false;
            if (!$hasBgImage) {
                // Add inline style to the first header container
                $out = preg_replace(
                    '/<div([^>]*?)class=\"header\"([^>]*)>/',
                    '<div$1class="header"$2 style="background-image:url(' . str_replace('/', '\/', $headerDataUri) . '); background-size:cover; background-position:center;">',
                    $out,
                    1
                ) ?? $out;
            }
        }

        return $out;
    }

    public function failed(\Throwable $e): void
    {
        try {
            $application = Application::find($this->applicationId);
            if ($application) {
                $this->updateMeta($application, ['status' => 'failed']);
                $application->save();
            }
        } catch (\Throwable $inner) {}
        Log::error('GenerateApplication: Job failed', [
            'application_id' => $this->applicationId,
            'error' => $e->getMessage(),
        ]);
    }
}
