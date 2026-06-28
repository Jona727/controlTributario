<?php

declare(strict_types=1);

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;

class PdfService
{
    /**
     * Genera un PDF de factura/boleta tributaria a partir de los datos.
     *
     * @param array $invoice Datos de la factura
     * @param array $user Datos del comercio / contribuyente
     * @param array $items Detalle de los conceptos facturados
     * @return string Contenido binario del PDF
     */
    public function generateInvoicePdf(array $invoice, array $user, array $items): string
    {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Helvetica');

        $dompdf = new Dompdf($options);

        // Diseñar una boleta municipal hermosa
        $html = $this->getPdfTemplate($invoice, $user, $items);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    private function getPdfTemplate(array $invoice, array $user, array $items): string
    {
        $issueDate = date('d/m/Y', strtotime($invoice['issue_date']));
        $dueDate = date('d/m/Y', strtotime($invoice['due_date']));
        $totalAmount = number_format((float)$invoice['total_amount'], 2, ',', '.');

        // Formatear Período (de YYYY-MM a MM/YYYY)
        $formattedPeriod = $invoice['period'] ?? '';
        if (preg_match('/^(\d{4})-(\d{2})$/', $formattedPeriod, $matches)) {
            $formattedPeriod = $matches[2] . '/' . $matches[1];
        }

        // Obtener períodos adeudados (facturas pendientes o vencidas previas de este usuario)
        $invoicesAdeudadas = 'Ninguno';
        try {
            $db = \App\Config\Database::getConnection();
            $stmt = $db->prepare("
                SELECT period 
                FROM invoices 
                WHERE user_id = :uid AND status IN ('pending', 'overdue') AND id != :current_id
                ORDER BY due_date ASC
            ");
            $stmt->execute([':uid' => (int)$user['id'], ':current_id' => (int)$invoice['id']]);
            $pendingPeriods = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            
            $formattedPending = [];
            foreach ($pendingPeriods as $p) {
                if (preg_match('/^(\d{4})-(\d{2})$/', $p, $m)) {
                    $formattedPending[] = (int)$m[2] . '/' . $m[1];
                } else {
                    $formattedPending[] = $p;
                }
            }
            if (!empty($formattedPending)) {
                $invoicesAdeudadas = implode(' ', $formattedPending);
            }
        } catch (\Exception $e) {
            $invoicesAdeudadas = 'Ninguno';
        }

        // Determinar el rubro según el nombre del comercio (para mayor realismo tributario)
        $rubro = 'Comercio General / Tasa Higiene y Profilaxis';
        $bName = mb_strtolower($user['business_name']);
        if (strpos($bName, 'panader') !== false) {
            $rubro = 'Panadería y Elaboración de Alimentos';
        } elseif (strpos($bName, 'farmac') !== false) {
            $rubro = 'Farmacia y Venta de Productos Medicinales';
        } elseif (strpos($bName, 'ferret') !== false) {
            $rubro = 'Venta de Artículos de Ferretería y Pintura';
        } elseif (strpos($bName, 'kios') !== false) {
            $rubro = 'Kiosco, Almacén y Bebidas';
        } elseif (strpos($bName, 'librer') !== false) {
            $rubro = 'Venta de Libros, Papelería y Afines';
        }

        // Formatear código de registro del contribuyente (Registro)
        $cleanCode = preg_replace('/[^0-9]/', '', $user['client_code']);
        $registroNum = str_pad($cleanCode ?: '0', 5, '0', STR_PAD_LEFT);

        // Generar número de código de barra municipal
        $pClean = '';
        if (preg_match('/^(\d{4})-(\d{2})$/', $invoice['period'] ?? '', $m)) {
            $pClean = $m[2] . $m[1];
        } else {
            $pClean = preg_replace('/[^0-9]/', '', $invoice['period'] ?? '');
        }
        $periodCode = str_pad($pClean, 7, '0', STR_PAD_LEFT);
        $dueDateClean = date('dmy', strtotime($invoice['due_date']));
        $amountCents = round($invoice['total_amount'] * 100);
        $amountClean = str_pad((string)$amountCents, 8, '0', STR_PAD_LEFT);
        
        // Formato de código de barras: Prefijo + Registro + Período + Vencimiento + Importe (repetido 3 veces para vencimientos)
        $barcodeText = '0179002' . $registroNum . $periodCode . $dueDateClean . $amountClean . $amountClean . $amountClean;

        // Generar barras HTML del código de barras
        $barcodeHtml = '';
        $barcodeLen = strlen($barcodeText);
        for ($i = 0; $i < $barcodeLen; $i++) {
            $digit = (int)$barcodeText[$i];
            $w1 = ($digit % 3) + 1;
            $w2 = (($digit + 2) % 3) + 1;
            $barcodeHtml .= "<div style='display: inline-block; width: {$w1}px; height: 35px; background-color: #000; margin-right: {$w2}px;'></div>";
        }

        $notesContent = !empty($invoice['notes']) ? htmlspecialchars($invoice['notes']) : 'Sin observaciones.';

        return "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <style>
                body {
                    font-family: 'Helvetica', 'Arial', sans-serif;
                    color: #000000;
                    font-size: 10px;
                    line-height: 1.3;
                    margin: 0;
                    padding: 0;
                }
                .outer-border {
                    border: 2px solid #000000;
                    padding: 15px;
                    margin: 0;
                    box-sizing: border-box;
                }
                .header-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 5px;
                }
                .logo-circle {
                    width: 45px;
                    height: 45px;
                    border-radius: 50%;
                    border: 2px double #000000;
                    background-color: #ffffff;
                    display: inline-block;
                    vertical-align: middle;
                    text-align: center;
                    line-height: 41px;
                    color: #000000;
                    font-weight: bold;
                    font-size: 16px;
                    margin-right: 8px;
                }
                .logo-text {
                    display: inline-block;
                    vertical-align: middle;
                }
                .logo-title {
                    font-family: 'Georgia', serif;
                    font-size: 13px;
                    font-weight: bold;
                    margin: 0;
                }
                .logo-subtitle {
                    font-size: 7.5px;
                    margin: 0;
                    color: #000000;
                }
                .header-right {
                    text-align: right;
                    font-size: 8.5px;
                }
                .header-right .original-box {
                    font-size: 11px;
                    font-weight: bold;
                    letter-spacing: 0.5px;
                    margin-bottom: 2px;
                }
                .doc-title {
                    text-align: center;
                    font-family: 'Georgia', serif;
                    font-size: 13px;
                    font-weight: bold;
                    font-style: italic;
                    margin: 10px 0;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }
                .grid-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 8px;
                }
                .grid-table td {
                    border: 1px solid #000000;
                    padding: 5px 6px;
                    font-size: 9.5px;
                    vertical-align: middle;
                }
                .grid-label {
                    font-weight: bold;
                }
                .col-3 {
                    width: 33.33%;
                }
                .footer-section {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 10px;
                }
                .footer-left {
                    width: 65%;
                    vertical-align: top;
                    padding-right: 15px;
                }
                .footer-right {
                    width: 35%;
                    border: 1px solid #000000;
                    height: 100px;
                    vertical-align: top;
                    text-align: center;
                }
                .stamp-box-inner {
                    height: 80px;
                }
                .stamp-label {
                    font-size: 8px;
                    border-top: 1px solid #000000;
                    padding: 3px 0;
                    background-color: #ffffff;
                    font-weight: bold;
                }
                .warning-text {
                    font-size: 7px;
                    line-height: 1.25;
                    margin-bottom: 10px;
                    text-align: justify;
                }
                .barcode-container {
                    text-align: center;
                }
                .barcode-number {
                    font-family: monospace;
                    font-size: 8px;
                    letter-spacing: 0.5px;
                    margin-top: 3px;
                    text-align: center;
                }
            </style>
        </head>
        <body>
            <div class='outer-border'>
                <!-- HEADER -->
                <table class='header-table'>
                    <tr>
                        <td style='width: 70%;'>
                            <div class='logo-circle'>EP</div>
                            <div class='logo-text'>
                                <h1 class='logo-title'>Municipio El Pingo</h1>
                                <p class='logo-subtitle'>Juan Domingo Perón - El Pingo (3132) - Entre Ríos</p>
                                <p class='logo-subtitle'>Tel./Fax int 28 - e-mail: rentas@elpingo.gob.ar</p>
                            </div>
                        </td>
                        <td class='header-right' style='width: 30%;'>
                            <div class='original-box'>ORIGINAL</div>
                            <div>Categoría: 00</div>
                        </td>
                    </tr>
                </table>

                <!-- TITULO BOLETA -->
                <div class='doc-title'>Boleta de Liquidación Tasa Higiene y Profilaxis</div>

                <!-- DATOS DEL CONTRIBUYENTE -->
                <table class='grid-table'>
                    <tr>
                        <td colspan='3'><span class='grid-label'>Tipo Régimen:</span> General</td>
                    </tr>
                    <tr>
                        <td colspan='2' style='width: 65%;'><span class='grid-label'>Nombre:</span> " . htmlspecialchars($user['business_name']) . "</td>
                        <td style='width: 35%;'><span class='grid-label'>Registro:</span> {$registroNum}</td>
                    </tr>
                    <tr>
                        <td colspan='3'><span class='grid-label'>Razón Soc.:</span> " . htmlspecialchars($user['business_name']) . "</td>
                    </tr>
                    <tr>
                        <td colspan='3'><span class='grid-label'>Domicilio:</span> " . htmlspecialchars($user['address']) . "</td>
                    </tr>
                    <tr>
                        <td class='col-3'><span class='grid-label'>Manzana:</span> –</td>
                        <td class='col-3'><span class='grid-label'>Lote/Dpto:</span> –</td>
                        <td class='col-3'><span class='grid-label'>Barrio:</span> –</td>
                    </tr>
                    <tr>
                        <td colspan='3'><span class='grid-label'>Rubro:</span> {$rubro}</td>
                    </tr>
                </table>

                <!-- LIQUIDACION Y VENCIMIENTO -->
                <table class='grid-table'>
                    <tr>
                        <td class='col-3'><span class='grid-label'>Vence:</span> {$dueDate}</td>
                        <td class='col-3'><span class='grid-label'>Período:</span> {$formattedPeriod}</td>
                        <td class='col-3' style='background-color:#fafafa;'><span class='grid-label'>Total:</span> <span style='font-size: 12px; font-weight: bold;'>$ {$totalAmount}</span></td>
                    </tr>
                    <tr>
                        <td colspan='3'><span class='grid-label'>Períodos Adeudados:</span> {$invoicesAdeudadas}</td>
                    </tr>
                </table>

                <!-- SECCION DE OBSERVACIONES -->
                <div style='margin-top: 5px; font-size: 8.5px;'>
                    <strong>Obs:</strong>
                    <div style='border: 1px solid #000000; padding: 4px 6px; min-height: 35px; margin-top: 2px; font-size: 8px;'>
                        {$notesContent}
                    </div>
                </div>

                <!-- FOOTER: ADVERTENCIA, BARRAS Y SELLO -->
                <table class='footer-section'>
                    <tr>
                        <td class='footer-left'>
                            <div class='warning-text'>
                                Recuerde: Que al finalizar con su actividad comercial deberá concurrir a la oficina de rentas municipal con el fin de informar el cese definitivo de las actividades para evitar generar multas intereses y demas accesorios.
                            </div>
                            <div class='barcode-container'>
                                <div style='display: block; margin: 0 auto; text-align: center; height: 35px; overflow: hidden;'>
                                    {$barcodeHtml}
                                </div>
                                <div class='barcode-number'>{$barcodeText}</div>
                            </div>
                        </td>
                        <td class='footer-right'>
                            <div class='stamp-box-inner'></div>
                            <div class='stamp-label'>Firma y Sello de Caja</div>
                        </td>
                    </tr>
                </table>
            </div>
        </body>
        </html>
        ";
    }
    
/**
     * Genera un PDF de Recibo de Pago Oficial.
     */
    public function generateReceiptPdf(array $payment, array $invoice, array $user): string
    {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Helvetica');

        $dompdf = new Dompdf($options);
        $html = $this->getReceiptTemplate($payment, $invoice, $user);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    private function getReceiptTemplate(array $payment, array $invoice, array $user): string
    {
        $paymentDate = date('d/m/Y H:i:s', strtotime($payment['payment_date']));
        $dueDate = date('d/m/Y', strtotime($invoice['due_date']));
        $amountPaid = number_format((float)$payment['amount_paid'], 2, ',', '.');
        $surchargePaid = number_format((float)$payment['surcharge_paid'], 2, ',', '.');
        $subtotal = number_format((float)$invoice['subtotal'], 2, ',', '.');

        return "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <style>
                body {
                    font-family: 'Helvetica', 'Arial', sans-serif;
                    color: #333;
                    font-size: 11px;
                    line-height: 1.4;
                    margin: 0;
                    padding: 0;
                }
                .container {
                    padding: 20px;
                    border: 1px solid #d1d5db;
                    margin: 10px;
                    background-color: #ffffff;
                }
                .header-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 20px;
                    border-bottom: 2px solid #111827;
                    padding-bottom: 10px;
                }
                .logo-container {
                    width: 50%;
                    text-align: left;
                }
                .logo-circle {
                    display: inline-block;
                    width: 45px;
                    height: 45px;
                    background-color: #111827;
                    color: white;
                    border-radius: 8px;
                    text-align: center;
                    line-height: 45px;
                    font-weight: bold;
                    font-size: 20px;
                }
                .logo-text {
                    display: inline-block;
                    vertical-align: middle;
                    margin-left: 10px;
                }
                .logo-title {
                    font-size: 14px;
                    font-weight: bold;
                    color: #111827;
                    margin: 0;
                }
                .logo-subtitle {
                    font-size: 9px;
                    color: #4b5563;
                    margin: 0;
                    text-transform: uppercase;
                }
                .receipt-info-container {
                    width: 50%;
                    text-align: right;
                    vertical-align: middle;
                }
                .receipt-title {
                    font-size: 14px;
                    font-weight: bold;
                    color: #111827;
                    margin: 0 0 5px 0;
                    letter-spacing: 0.05em;
                }
                .receipt-number {
                    font-size: 16px;
                    font-weight: bold;
                    color: #dc2626;
                }
                .section-title {
                    font-size: 11px;
                    font-weight: bold;
                    text-transform: uppercase;
                    background-color: #f3f4f6;
                    padding: 6px 10px;
                    margin-top: 20px;
                    margin-bottom: 8px;
                    border-left: 3px solid #111827;
                    color: #111827;
                }
                .info-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 15px;
                }
                .info-table td {
                    padding: 6px 8px;
                    vertical-align: top;
                }
                .info-label {
                    font-weight: bold;
                    color: #4b5563;
                    width: 25%;
                }
                .info-value {
                    color: #111827;
                }
                .details-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 10px;
                    margin-bottom: 25px;
                }
                .details-table th {
                    background-color: #111827;
                    color: white;
                    font-weight: bold;
                    text-transform: uppercase;
                    font-size: 10px;
                    padding: 8px;
                    text-align: left;
                }
                .details-table td {
                    padding: 10px 8px;
                    border-bottom: 1px solid #e5e7eb;
                }
                .totals-table {
                    width: 45%;
                    float: right;
                    border-collapse: collapse;
                    margin-bottom: 40px;
                }
                .totals-table td {
                    padding: 8px;
                    border-bottom: 1px solid #e5e7eb;
                }
                .totals-label {
                    font-weight: bold;
                    color: #4b5563;
                }
                .totals-value {
                    text-align: right;
                    font-weight: bold;
                }
                .totals-grand {
                    font-size: 14px;
                    color: #111827;
                    background-color: #f9fafb;
                    border-bottom: 2px solid #111827 !important;
                }
                .clear {
                    clear: both;
                }
                .legal-footer {
                    margin-top: 40px;
                    border-top: 1px solid #e5e7eb;
                    padding-top: 15px;
                    text-align: center;
                }
                .stamp-container {
                    float: right;
                    width: 180px;
                    height: 90px;
                    border: 2px dashed #9ca3af;
                    border-radius: 6px;
                    text-align: center;
                    padding: 10px;
                    margin-top: 20px;
                    color: #6b7280;
                }
                .stamp-title {
                    font-size: 9px;
                    font-weight: bold;
                    text-transform: uppercase;
                    margin-bottom: 25px;
                }
                .stamp-text {
                    font-size: 8px;
                    color: #9ca3af;
                    text-transform: uppercase;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <!-- HEADER -->
                <table class='header-table'>
                    <tr>
                        <td class='logo-container'>
                            <div class='logo-circle'>MT</div>
                            <div class='logo-text'>
                                <h1 class='logo-title'>MUNICIPALIDAD</h1>
                                <p class='logo-subtitle'>Dirección de Tesorería y Finanzas</p>
                            </div>
                        </td>
                        <td class='receipt-info-container'>
                            <h2 class='receipt-title'>RECIBO OFICIAL DE PAGO</h2>
                            <div class='receipt-number'>{$payment['receipt_number']}</div>
                        </td>
                    </tr>
                </table>

                <!-- DETALLES DE TRANSACCIÓN -->
                <div class='section-title'>Detalles de la Transacción</div>
                <table class='info-table'>
                    <tr>
                        <td class='info-label'>Fecha y Hora de Pago:</td>
                        <td class='info-value'><strong>{$paymentDate}</strong></td>
                        <td class='info-label'>Cajero / Operador:</td>
                        <td class='info-value'>Municipalidad de Rentas (Caja 1)</td>
                    </tr>
                    <tr>
                        <td class='info-label'>Medio de Pago:</td>
                        <td class='info-value'><strong>EFECTIVO (Ventanilla)</strong></td>
                        <td class='info-label'>Estado de Caja:</td>
                        <td class='info-value' style='color:#059669; font-weight:bold;'>PROCESADO / COBRADO</td>
                    </tr>
                </table>

                <!-- DATOS DEL CONTRIBUYENTE -->
                <div class='section-title'>Datos del Contribuyente</div>
                <table class='info-table'>
                    <tr>
                        <td class='info-label'>Razón Social:</td>
                        <td class='info-value'><strong>" . htmlspecialchars($user['business_name']) . "</strong></td>
                        <td class='info-label'>Código de Cliente:</td>
                        <td class='info-value'>" . htmlspecialchars($user['client_code']) . "</td>
                    </tr>
                    <tr>
                        <td class='info-label'>CUIT:</td>
                        <td class='info-value'>" . htmlspecialchars($user['cuit']) . "</td>
                        <td class='info-label'>Domicilio Comercial:</td>
                        <td class='info-value'>" . htmlspecialchars($user['address']) . "</td>
                    </tr>
                </table>

                <!-- CONCEPTOS CANCELADOS -->
                <div class='section-title'>Conceptos Cancelados</div>
                <table class='details-table'>
                    <thead>
                        <tr>
                            <th style='text-align: left;'>Obligación Tributaria</th>
                            <th style='width: 25%; text-align: center;'>Período</th>
                            <th style='width: 30%; text-align: right;'>Fecha Vto. Original</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Tasa de Seguridad e Higiene (Boleta {$invoice['invoice_number']})</strong></td>
                            <td style='text-align: center;'>{$invoice['period']}</td>
                            <td style='text-align: right;'>{$dueDate}</td>
                        </tr>
                    </tbody>
                </table>

                <!-- DESGLOSE DE CAJA -->
                <table class='totals-table'>
                    <tr>
                        <td class='totals-label'>Monto Base:</td>
                        <td class='totals-value'>$ {$subtotal}</td>
                    </tr>
                    <tr>
                        <td class='totals-label'>Recargos cobrados (Mora):</td>
                        <td class='totals-value'>$ {$surchargePaid}</td>
                    </tr>
                    <tr class='totals-grand'>
                        <td class='totals-label' style='font-size:12px; font-weight:bold;'>TOTAL RECAUDADO:</td>
                        <td class='totals-value' style='font-size:12px; font-weight:bold;'>$ {$amountPaid}</td>
                    </tr>
                </table>

                <div class='clear'></div>

                <!-- SELLO DE TESORERÍA MUNICIPAL -->
                <div class='stamp-container'>
                    <div class='stamp-title'>CAJA DE TESORERÍA</div>
                    <div style='font-size: 14px; font-weight: bold; color: #111827; margin-bottom: 5px;'>COBRADO</div>
                    <div class='stamp-text'>Municipalidad de Rentas</div>
                </div>

                <div style='width: 60%; float: left; margin-top: 30px; font-size: 9px; color: #6b7280; line-height: 1.4;'>
                    <strong>Nota Impositiva Importante:</strong><br>
                    El presente recibo oficial constituye constancia legal y suficiente de cancelación y libre deuda para la Tasa de Seguridad e Higiene correspondiente al período fiscal <strong>{$invoice['period']}</strong>. Guarde este documento como comprobante de pago oficial ante cualquier inspección municipal.
                </div>

                <div class='clear'></div>

                <div class='legal-footer'>
                    <p style='font-size: 9px; color: #9ca3af; margin: 0;'>Documento electrónico oficial emitido por la Dirección General de Rentas de la Municipalidad.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Genera un PDF de Rendición y Cierre de Caja Diario.
     */
    public function generateCashClosurePdf(array $payments, float $totalBase, float $totalMora, float $totalPaid, string $date): string
    {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Helvetica');

        $dompdf = new Dompdf($options);
        $html = $this->getCashClosureTemplate($payments, $totalBase, $totalMora, $totalPaid, $date);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape'); // Cierre de caja en horizontal queda mejor
        $dompdf->render();

        return $dompdf->output();
    }

    private function getCashClosureTemplate(array $payments, float $totalBase, float $totalMora, float $totalPaid, string $date): string
    {
        $formattedDate = date('d/m/Y', strtotime($date));
        $formattedBase = number_format($totalBase, 2, ',', '.');
        $formattedMora = number_format($totalMora, 2, ',', '.');
        $formattedPaid = number_format($totalPaid, 2, ',', '.');

        $rowsHtml = '';
        if (empty($payments)) {
            $rowsHtml = "<tr><td colspan='9' style='text-align: center; padding: 15px; color:#6b7280;'>Sin transacciones registradas en el día.</td></tr>";
        } else {
            foreach ($payments as $p) {
                $pDate = date('H:i:s', strtotime($p['payment_date']));
                $pBase = number_format(floatval($p['amount_paid']) - floatval($p['surcharge_paid']), 2, ',', '.');
                $pMora = number_format(floatval($p['surcharge_paid']), 2, ',', '.');
                $pTotal = number_format(floatval($p['amount_paid']), 2, ',', '.');
                
                $rowsHtml .= "
                    <tr>
                        <td style='padding: 6px; border-bottom: 1px solid #ddd; text-align: center;'>{$pDate} hs</td>
                        <td style='padding: 6px; border-bottom: 1px solid #ddd; text-align: left; font-weight: bold;'>{$p['business_name']}</td>
                        <td style='padding: 6px; border-bottom: 1px solid #ddd; text-align: center;'>{$p['cuit']}</td>
                        <td style='padding: 6px; border-bottom: 1px solid #ddd; text-align: center; color: #dc2626; font-weight: bold;'>{$p['receipt_number']}</td>
                        <td style='padding: 6px; border-bottom: 1px solid #ddd; text-align: center;'>{$p['invoice_number']}</td>
                        <td style='padding: 6px; border-bottom: 1px solid #ddd; text-align: center;'>{$p['period']}</td>
                        <td style='padding: 6px; border-bottom: 1px solid #ddd; text-align: right;'>$ {$pBase}</td>
                        <td style='padding: 6px; border-bottom: 1px solid #ddd; text-align: right; color:#dc2626;'>$ {$pMora}</td>
                        <td style='padding: 6px; border-bottom: 1px solid #ddd; text-align: right; font-weight: bold; color: #059669;'>$ {$pTotal}</td>
                    </tr>
                ";
            }
        } 

        return "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <style>
                body {
                    font-family: 'Helvetica', 'Arial', sans-serif;
                    color: #333;
                    font-size: 10px;
                    line-height: 1.3;
                    margin: 0;
                    padding: 0;
                }
                .container {
                    padding: 15px;
                }
                .header-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 15px;
                    border-bottom: 2px solid #111827;
                    padding-bottom: 8px;
                }
                .logo-container {
                    width: 60%;
                    text-align: left;
                }
                .logo-circle {
                    display: inline-block;
                    width: 40px;
                    height: 40px;
                    background-color: #111827;
                    color: white;
                    border-radius: 6px;
                    text-align: center;
                    line-height: 40px;
                    font-weight: bold;
                    font-size: 18px;
                }
                .logo-text {
                    display: inline-block;
                    vertical-align: middle;
                    margin-left: 8px;
                }
                .logo-title {
                    font-size: 12px;
                    font-weight: bold;
                    color: #111827;
                    margin: 0;
                }
                .logo-subtitle {
                    font-size: 8px;
                    color: #4b5563;
                    margin: 0;
                    text-transform: uppercase;
                }
                .info-container {
                    width: 40%;
                    text-align: right;
                    vertical-align: middle;
                }
                .title-doc {
                    font-size: 13px;
                    font-weight: bold;
                    color: #111827;
                    margin: 0 0 3px 0;
                    text-transform: uppercase;
                }
                .doc-date {
                    font-size: 10px;
                    font-weight: bold;
                    color: #4b5563;
                }
                .summary-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 15px;
                    background-color: #f9fafb;
                    border: 1px solid #e5e7eb;
                }
                .summary-table td {
                    padding: 8px;
                    text-align: center;
                    border-right: 1px solid #e5e7eb;
                }
                .summary-table td:last-child {
                    border-right: none;
                }
                .summary-label {
                    font-size: 8px;
                    color: #4b5563;
                    text-transform: uppercase;
                    margin-bottom: 4px;
                    font-weight: bold;
                }
                .summary-value {
                    font-size: 14px;
                    font-weight: bold;
                    color: #111827;
                }
                .details-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 40px;
                }
                .details-table th {
                    background-color: #111827;
                    color: white;
                    font-weight: bold;
                    text-transform: uppercase;
                    font-size: 8px;
                    padding: 6px;
                }
                .signature-section {
                    width: 100%;
                    margin-top: 40px;
                }
                .signature-box {
                    width: 30%;
                    text-align: center;
                    vertical-align: top;
                }
                .signature-line {
                    width: 80%;
                    border-top: 1px solid #9ca3af;
                    margin: 40px auto 5px auto;
                }
                .signature-title {
                    font-size: 8px;
                    font-weight: bold;
                    color: #4b5563;
                    text-transform: uppercase;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <!-- HEADER -->
                <table class='header-table'>
                    <tr>
                        <td class='logo-container'>
                            <div class='logo-circle'>MT</div>
                            <div class='logo-text'>
                                <h1 class='logo-title'>MUNICIPALIDAD DE EL PINGO</h1>
                                <p class='logo-subtitle'>Dirección General de Tesorería y Rentas</p>
                            </div>
                        </td>
                        <td class='info-container'>
                            <h2 class='title-doc'>Planilla de Rendición y Cierre de Caja</h2>
                            <div class='doc-date'>Fecha: {$formattedDate}</div>
                        </td>
                    </tr>
                </table>

                <!-- RESUMEN DE TOTALES -->
                <table class='summary-table'>
                    <tr>
                        <td>
                            <div class='summary-label'>Subtotal Recaudado (Tasa Base)</div>
                            <div class='summary-value'>$ {$formattedBase}</div>
                        </td>
                        <td>
                            <div class='summary-label'>Mora Recaudada (Intereses)</div>
                            <div class='summary-value' style='color:#dc2626;'>$ {$formattedMora}</div>
                        </td>
                        <td style='background-color:#d1fae5;'>
                            <div class='summary-label' style='color:#065f46;'>TOTAL RECAUDADO (EFECTIVO)</div>
                            <div class='summary-value' style='color:#059669; font-size:16px;'>$ {$formattedPaid}</div>
                        </td>
                        <td>
                            <div class='summary-label'>Boletas Cobradas</div>
                            <div class='summary-value'>" . count($payments) . "</div>
                        </td>
                    </tr>
                </table>

                <!-- DETALLE DE TRANSACCIONES -->
                <table class='details-table'>
                    <thead>
                        <tr>
                            <th style='width: 8%;'>Hora</th>
                            <th>Razón Social / Comercio</th>
                            <th style='width: 12%;'>CUIT</th>
                            <th style='width: 12%;'>Nº Recibo</th>
                            <th style='width: 10%;'>Nº Factura</th>
                            <th style='width: 8%;'>Período</th>
                            <th style='width: 10%; text-align: right;'>Monto Base</th>
                            <th style='width: 10%; text-align: right;'>Mora</th>
                            <th style='width: 12%; text-align: right;'>Total Cobrado</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$rowsHtml}
                    </tbody>
                </table>

                <!-- SECCIÓN DE FIRMAS -->
                <table class='signature-section' style='width: 100%; border-collapse: collapse;'>
                    <tr>
                        <td class='signature-box'>
                            <div class='signature-line'></div>
                            <div class='signature-title'>Firma del Cajero</div>
                            <div style='font-size: 8px; color: #9ca3af;'>Rendición de Ventanilla</div>
                        </td>
                        <td style='width: 40%;'></td>
                        <td class='signature-box'>
                            <div class='signature-line'></div>
                            <div class='signature-title'>Firma del Tesorero</div>
                            <div style='font-size: 8px; color: #9ca3af;'>Recepción y Control</div>
                        </td>
                    </tr>
                </table>
            </div>
        </body>
        </html>
        ";
    }
}

