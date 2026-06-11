<?php
require_once dirname(__FILE__) . '/tcpdf/tcpdf.php';

class Pdf extends TCPDF
{
    public $headerData  = [];
    public $footerData  = [];

    function __construct()
    {
        parent::__construct();
    }

    // =============================================
    // COMMON HEADER — runs automatically every page
    // =============================================
    public function Header()
    {
        if (empty($this->headerData)) return;

        $company          = $this->headerData['company'];
        $proforma_invoice = $this->headerData['proforma_invoice'];
        $dealer           = $this->headerData['dealer'];

        $x = 8;
        $y = 6;

        /* ---- Logo ---- */
        $pageWidth       = 194;
        $topSectionWidth = $pageWidth / 2;
        $logoX           = $x + 2;
        $logoY           = $y;
        $logoHeight      = 14;

        if (!empty($company['logo_path']) && file_exists($company['logo_path'])) {
            $this->Image($company['logo_path'], $logoX, $logoY, 0, $logoHeight);
        }

        /* ---- Right Details ---- */
        $textX      = $x + $topSectionWidth + 4;
        $labelWidth = 20;
        $valueWidth = $topSectionWidth - 8 - $labelWidth;

        $this->SetXY($textX, $logoY);
        $this->SetFont('helvetica', 'B', 9);
        $this->Cell($labelWidth, 4, 'CIN :', 0, 0);
        $this->SetFont('helvetica', '', 9);
        $this->Cell($valueWidth, 4, $company['fld_cin'] ?? '', 0, 1);

        $this->SetX($textX);
        $this->SetFont('helvetica', 'B', 9);
        $this->Cell($labelWidth, 4, 'Address :', 0, 0);
        $this->SetFont('helvetica', '', 9);
        $this->MultiCell($valueWidth, 4, $company['fld_org_address'] ?? '', 0, 'L');

        $this->SetX($textX);
        $this->SetFont('helvetica', 'B', 9);
        $this->Cell($labelWidth, 4, 'GST No:', 0, 0);
        $this->SetFont('helvetica', '', 9);
        $this->Cell($valueWidth, 4, $company['fld_gst_no'] ?? '', 0, 1);

        $this->SetX($textX);
        $this->SetFont('helvetica', 'B', 9);
        $this->Cell($labelWidth, 4, 'Phone :', 0, 0);
        $this->SetFont('helvetica', '', 9);
        $this->Cell($valueWidth, 4, $company['fld_org_contact'] ?? '', 0, 1);

        $this->SetX($textX);
        $this->SetFont('helvetica', 'B', 9);
        $this->Cell($labelWidth, 4, 'Website :', 0, 0);
        $this->SetFont('helvetica', '', 9);
        $this->Cell($valueWidth, 4, $company['fld_website'] ?? '', 0, 1);

        /* ---- Vertical Navy Line ---- */
        $lineX      = $x + $topSectionWidth;
        $lineBottom = max($this->GetY(), $logoY + 18);
        $this->SetDrawColor(31, 56, 100);
        $this->SetLineWidth(0.3);
        $this->Line($lineX, $logoY, $lineX, $lineBottom);

        /* ---- Horizontal Line ---- */
        $headerEndY = $lineBottom + 1;
        $this->SetDrawColor(31, 56, 100);
        $this->SetLineWidth(0.5);
        $this->Line($x, $headerEndY, $x + 194, $headerEndY);

        /* ---- Proforma Invoice Banner ---- */
        $bannerY = $headerEndY + 1;
        $this->SetFillColor(44, 38, 84);
        $this->SetDrawColor(44, 38, 84);
        $this->Rect($x, $bannerY, 194, 7, 'F');
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('helvetica', 'B', 13);
        $this->SetXY($x, $bannerY);
        $this->Cell(194, 7, 'PROFORMA INVOICE', 0, 0, 'C');
        $this->SetTextColor(0, 0, 0);

        /* ---- Page 1: Show Bill To / Shipped / PI Details ---- */
        if ($this->getPage() == 1) {

            $contentY   = $bannerY + 8;
            $colWidth   = 73;
            $lineHeight = 4;

            $fullAddress = ($dealer['fld_dealer_address'] ?? '') . ', '
                         . ($dealer['fld_taluka_name']    ?? '') . ', '
                         . ($dealer['fld_dist_name']      ?? '') . ', '
                         . ($dealer['fld_state_name']     ?? '');

            /* Column 1: Bill To */
            $this->SetXY($x, $contentY);
            $this->SetFont('helvetica', 'B', 10);
            $this->SetTextColor(31, 56, 100);
            $this->Cell($colWidth, 5, 'Bill To,', 0, 1, 'L');
            $this->SetTextColor(0, 0, 0);

            $curY1 = $contentY + 5;
            $this->_inlineLine($x, 'Customer Name : ', $dealer['fld_dealer_name']     ?? '', $colWidth, $curY1);
            $this->_inlineLine($x, 'Address : ',       $fullAddress,                          $colWidth, $curY1);
            $this->_inlineLine($x, 'Contact Person : ',$dealer['contact_person_name'] ?? '', $colWidth, $curY1);
            $this->_inlineLine($x, 'Contact No : ',    $dealer['fld_mobile_no']       ?? '', $colWidth, $curY1);
            $this->_inlineLine($x, 'GSTIN : ',         $dealer['fld_gst_no']          ?? '', $colWidth, $curY1);
            $this->_inlineLine($x, 'E-mail : ',        $dealer['fld_email']           ?? '', $colWidth, $curY1);
            $endY1 = $curY1;

            /* Column 2: Shipped */
            $col2X = $x + $colWidth + 5;
            $this->SetXY($col2X, $contentY);
            $this->SetFont('helvetica', 'B', 10);
            $this->SetTextColor(31, 56, 100);
            $this->Cell($colWidth, 5, 'Shipped', 0, 1, 'L');
            $this->SetTextColor(0, 0, 0);

            $shipAddr = !empty($proforma_invoice['fld_shipping_address'])
                      ? $proforma_invoice['fld_shipping_address']
                      : $fullAddress;

            $curY2 = $contentY + 5;
            $this->_inlineLine($col2X, 'Customer Name : ', $dealer['fld_dealer_name']     ?? '', $colWidth, $curY2);
            $this->_inlineLine($col2X, 'Address : ',       $shipAddr,                             $colWidth, $curY2);
            $this->_inlineLine($col2X, 'Contact Person : ',$dealer['contact_person_name'] ?? '', $colWidth, $curY2);
            $this->_inlineLine($col2X, 'Contact No : ',    $dealer['fld_mobile_no']       ?? '', $colWidth, $curY2);
            $endY2 = $curY2;

            /* Column 3: Proforma Details */
            $col3X = $col2X + $colWidth + 2;
            $poDate = '-';
            if (
                !empty($proforma_invoice['fld_po_date']) &&
                $proforma_invoice['fld_po_date'] != '0000-00-00' &&
                $proforma_invoice['fld_po_date'] != '0000-00-00 00:00:00'
            ) {
                $poDate = date('d/m/Y', strtotime($proforma_invoice['fld_po_date']));
            }

            $curY3 = $contentY;
            $this->_inlineLine($col3X, 'Proforma No : ',    $proforma_invoice['fld_proforma_invoice_no']                                ?? '', $colWidth, $curY3);
            $this->_inlineLine($col3X, 'Date : ',           date('d/m/Y', strtotime($proforma_invoice['fld_proforma_invoice_date'])),           $colWidth, $curY3);
            $this->_inlineLine($col3X, 'PO/LOI NO : ',      $proforma_invoice['fld_po_no']   ?? '-',                                            $colWidth, $curY3);
            $this->_inlineLine($col3X, 'PO Date : ',        $poDate,                                                                             $colWidth, $curY3);
            $this->_inlineLine($col3X, 'Order Rec Mode : ', $proforma_invoice['fld_po_mode'] ?? '-',                                            $colWidth, $curY3);
            $this->_inlineLine($col3X, 'Prepared By : ',    $proforma_invoice['created_by_name'] ?? '',                                         $colWidth, $curY3);
            $endY3 = $curY3;

            /* Orange separators */
            $maxY = max($endY1, $endY2, $endY3);
            $this->SetDrawColor(240, 126, 27);
            $this->SetLineWidth(0.3);
            $this->Line($col2X - 5, $contentY, $col2X - 5, $maxY);
            $this->Line($col3X - 1, $contentY, $col3X - 1, $maxY);
            $this->Line($x,         $maxY + 1,  $x + 194,  $maxY + 1);
            $this->SetY($maxY + 2);

        } else {
            /* Pages 2+: just a thin orange line below banner */
            $this->SetY($bannerY + 9);
            $this->SetDrawColor(240, 126, 27);
            $this->SetLineWidth(0.3);
            $this->Line($x, $this->GetY(), $x + 194, $this->GetY());
            $this->SetY($this->GetY() + 2);
        }

        $this->SetLineWidth(0.2);
        $this->SetDrawColor(0, 0, 0);
    }

    // =============================================
    // COMMON FOOTER — runs automatically every page
    // =============================================
    public function Footer()
    {
        $this->SetY(-12);
        $x = 8;

        // Orange top line
        $this->SetDrawColor(240, 126, 27);
        $this->SetLineWidth(0.5);
        $this->Line($x, $this->GetY(), $x + 194, $this->GetY());
        $this->SetLineWidth(0.2);

        $this->SetFont('helvetica', 'I', 8);
        $this->SetTextColor(100, 100, 100);

        $company = $this->headerData['company'] ?? [];
        $orgName = $company['fld_org_name']     ?? '';
        $website = $company['fld_website']      ?? '';
        $phone   = $company['fld_org_contact']  ?? '';

        // Left: company info
        $this->Cell(100, 6, $orgName . ' | ' . $website . ' | ' . $phone, 0, 0, 'L');

        // Right: page number
        $this->Cell(0, 6, 'Page ' . $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages(), 0, 0, 'R');

        $this->SetTextColor(0, 0, 0);
    }

    // =============================================
    // HELPER: inline bold-label + value
    // =============================================
    public function _inlineLine($colX, $label, $value, $colWidth, &$curY)
    {
        $safeValue = htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
        $html      = '<b>' . $label . '</b>' . $safeValue;
        $this->SetFont('helvetica', '', 8);
        $this->writeHTMLCell($colWidth, 0, $colX, $curY, $html, 0, 1, false, true, 'L', true);
        $curY = $this->GetY();
    }
}
?>