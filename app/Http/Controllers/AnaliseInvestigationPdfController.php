<?php

namespace App\Http\Controllers;

use App\Models\AnaliseInvestigation;
use App\Services\AnaliseInteligente\Pdf\InvestigationPdfDataBuilder;
use App\Support\BrandingAsset;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class AnaliseInvestigationPdfController extends Controller
{
    public function __invoke(AnaliseInvestigation $investigation, InvestigationPdfDataBuilder $builder): Response
    {
        @ini_set('memory_limit', '1024M');
        @set_time_limit(0);

        $data = $builder->build($investigation);
        $data['brand_logo_data_uri'] = BrandingAsset::pdfDataUri();
        $filename = Str::slug((string) ($investigation->name ?: 'investigacao-' . $investigation->id));

        return Pdf::loadView('pdf.analise-investigation', $data)
            ->setPaper('a4', 'portrait')
            ->download($filename . '.pdf');
    }
}
