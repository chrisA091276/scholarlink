<?php

namespace App\Services;

use App\Models\ScholarshipApplication;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfDocument;

class ApplicationPdfService
{
    public function render(ScholarshipApplication $application): PdfDocument
    {
        $pdf = Pdf::loadView('applications.professional-pdf', compact('application'))->setPaper('a4', 'portrait');
        $pdf->render();
        $reference = $application->application_no;
        $pdf->getDomPDF()->getCanvas()->page_script(function ($page, $total, $canvas, $fonts) use ($reference) {
            $font = $fonts->getFont('Helvetica');
            $text = 'ADFC - Student Financial Assistance  |  '.$reference.'  |  '.$page.' / '.$total;
            $width = $fonts->getTextWidth($text, $font, 6);
            $canvas->text(($canvas->get_width() - $width) / 2, $canvas->get_height() - 22, $text, $font, 6, [0.42, 0.46, 0.50]);
        });

        return $pdf;
    }
}
