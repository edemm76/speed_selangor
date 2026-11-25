<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Report;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * SSMS-UC-600 Manage Report
 * - the officer opens an application and clicks "Muat Turun Laporan"
 * - the system generates the application report as a PDF and displays it [SSMS-REQ-601]
 * Rules (thesis Table 3.7):
 *   1. the report shows when it was generated and by whom
 *   2. every report is saved automatically (file + row in the `reports` table)
 */
class ReportController extends Controller
{
    /** Generate a new PDF report, save it, and show it */
    public function store(Application $application): Response
    {
        // Drafts are private to the applicant
        abort_if($application->current_status === ApplicationStatus::Draft, 404);

        $application->load([
            'vendor',
            'documents',
            'statusHistories.admin',
            'verifications.admin',
            'meetingDecision.recordedBy',
        ]);

        $admin = Auth::guard('admin')->user();
        $generatedAt = now();
        $reportNo = 'LPR-' . $application->reference_no . '-' . $generatedAt->format('YmdHis');

        $pdf = Pdf::loadView('admin.reports.application', [
            'application' => $application,
            'admin' => $admin,
            'generatedAt' => $generatedAt,
            'reportNo' => $reportNo,
            // Logo only when the GD extension is available (DomPDF needs it for PNG images)
            'logo' => extension_loaded('gd') && file_exists(public_path('images/logo.png'))
                ? public_path('images/logo.png') : null,
        ])->setPaper('a4');

        // Page numbers "Halaman X / Y" at the bottom right of every page
        $pdf->render();
        $dompdf = $pdf->getDomPDF();
        $canvas = $dompdf->getCanvas();
        $canvas->page_text(
            $canvas->get_width() - 100, $canvas->get_height() - 42,
            'Halaman {PAGE_NUM} / {PAGE_COUNT}',
            $dompdf->getFontMetrics()->getFont('DejaVu Sans'), 7, [0.4, 0.44, 0.54]
        );

        // Rule 2: keep a copy of every generated report
        $path = 'reports/' . $application->application_id . '/' . $reportNo . '.pdf';
        Storage::disk(config('speed.upload.disk'))->put($path, $pdf->output());

        $report = Report::create([
            'application_id' => $application->application_id,
            'generated_by_admin_id' => $admin->admin_id,
            'report_type' => 'application',
            'file_path' => $path,
        ]);

        return $this->pdfResponse($report);
    }

    /** Open a report generated earlier (from the report history on the application page) */
    public function show(Report $report): Response
    {
        return $this->pdfResponse($report);
    }

    /** Display the PDF in the browser (the officer can then download or print it) */
    private function pdfResponse(Report $report): Response
    {
        $disk = Storage::disk(config('speed.upload.disk'));
        abort_unless($disk->exists($report->file_path), 404);

        return $disk->response($report->file_path, basename($report->file_path), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . basename($report->file_path) . '"',
        ]);
    }
}
