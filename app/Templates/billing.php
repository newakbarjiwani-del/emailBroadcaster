<?php

function render_billing_email(array $message): array
{
    $items = parse_items($message['itemsJson'] ?? '[]');
    $rows = '';
    foreach ($items as $item) {
        $rows .= '<tr>
        <td style="padding:10px 12px;border-bottom:1px solid #eee;">' . escape_html($item['name'] ?? '') . '</td>
        <td style="padding:10px 12px;border-bottom:1px solid #eee;text-align:right;white-space:nowrap;">' . format_rupiah($item['amount'] ?? 0) . '</td>
      </tr>';
    }

    $outstandingBlock = '';
    if (isset($message['outstandingAmount']) && $message['outstandingAmount'] !== null && $message['outstandingAmount'] !== '') {
        $outstandingBlock = '<tr>
          <td style="padding:10px 12px;font-weight:700;">Outstanding / Sisa Cicilan</td>
          <td style="padding:10px 12px;text-align:right;font-weight:700;color:#c2410c;">' . format_rupiah($message['outstandingAmount']) . '</td>
        </tr>';
    }

    $paymentButton = '';
    if (!empty($message['paymentLink'])) {
        $paymentButton = '<p style="margin:28px 0 8px;text-align:center;">
        <a href="' . escape_html($message['paymentLink']) . '" style="display:inline-block;background:#ea580c;color:#fff;text-decoration:none;padding:14px 28px;border-radius:8px;font-weight:700;">Bayar Disini</a>
      </p>';
    }

    $guide = '';
    if (!empty($message['paymentGuide'])) {
        foreach (preg_split("/\r\n|\n|\r/", (string) $message['paymentGuide']) as $line) {
            $guide .= '<div>' . escape_html($line) . '</div>';
        }
    }

    $classRow = !empty($message['studentClass'])
        ? '<tr><td style="padding:6px 0;color:#6b7280;">Kelas</td><td style="padding:6px 0;">' . escape_html($message['studentClass']) . '</td></tr>'
        : '';
    $nisRow = !empty($message['studentNis'])
        ? '<tr><td style="padding:6px 0;color:#6b7280;">NIS</td><td style="padding:6px 0;">' . escape_html($message['studentNis']) . '</td></tr>'
        : '';
    $vaRow = !empty($message['vaNumber'])
        ? '<tr><td style="padding:6px 0;color:#6b7280;">Nomor VA</td><td style="padding:6px 0;font-weight:700;letter-spacing:.3px;">' . escape_html($message['vaNumber']) . '</td></tr>'
        : '';
    $invoiceRow = !empty($message['orderNumber'])
        ? '<tr><td style="padding:6px 0;color:#6b7280;">No. Invoice</td><td style="padding:6px 0;">' . escape_html($message['orderNumber']) . '</td></tr>'
        : '';

    $guideBlock = $guide !== ''
        ? '<div style="margin-top:20px;padding:14px 16px;background:#fff7ed;border:1px solid #fed7aa;border-radius:8px;font-size:13px;line-height:1.6;color:#9a3412;">
                    <div style="font-weight:700;margin-bottom:6px;">Tata Cara Pembayaran</div>
                    ' . $guide . '
                  </div>'
        : '';

    $html = '<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#111827;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f4f6;padding:24px 12px;">
    <tr><td align="center">
      <table role="presentation" width="100%" style="max-width:640px;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.08);">
        <tr>
          <td style="background:#0f766e;color:#fff;padding:20px 24px;">
            <div style="font-size:18px;font-weight:700;">Notifikasi Tagihan</div>
            <div style="opacity:.9;margin-top:4px;font-size:13px;">Assalamu\'alaikum Warrahmatullahi Wabarakatuh</div>
          </td>
        </tr>
        <tr>
          <td style="padding:24px;">
            <p style="margin:0 0 16px;line-height:1.5;">
              Berikut tagihan yang harus dibayarkan dengan rincian sebagai berikut:
            </p>

            <table role="presentation" width="100%" style="border-collapse:collapse;margin-bottom:18px;font-size:14px;">
              <tr><td style="padding:6px 0;width:140px;color:#6b7280;">Nama</td><td style="padding:6px 0;font-weight:600;">' . escape_html($message['studentName'] ?? '') . '</td></tr>
              ' . $classRow . $nisRow . $vaRow . $invoiceRow . '
            </table>

            <div style="font-size:14px;font-weight:700;margin:0 0 8px;">Rincian Pembayaran</div>
            <table role="presentation" width="100%" style="border-collapse:collapse;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;font-size:14px;">
              <thead>
                <tr style="background:#f9fafb;">
                  <th align="left" style="padding:10px 12px;">Jenis Tagihan</th>
                  <th align="right" style="padding:10px 12px;">Nominal</th>
                </tr>
              </thead>
              <tbody>
                ' . ($rows !== '' ? $rows : '<tr><td colspan="2" style="padding:12px;color:#6b7280;">Tidak ada item</td></tr>') . '
                <tr>
                  <td style="padding:10px 12px;font-weight:700;background:#f9fafb;">Total Tagihan</td>
                  <td style="padding:10px 12px;text-align:right;font-weight:700;background:#f9fafb;">' . format_rupiah($message['totalAmount'] ?? 0) . '</td>
                </tr>
                ' . $outstandingBlock . '
              </tbody>
            </table>

            ' . $paymentButton . '
            ' . $guideBlock . '
          </td>
        </tr>
        <tr>
          <td style="padding:14px 24px;background:#f9fafb;color:#6b7280;font-size:12px;">
            Email ini dikirim otomatis. Mohon tidak membalas email ini.
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body>
</html>';

    $textItems = [];
    foreach ($items as $i) {
        $textItems[] = '- ' . ($i['name'] ?? '') . ': ' . format_rupiah($i['amount'] ?? 0);
    }

    $text = "Assalamu'alaikum Warrahmatullahi Wabarakatuh\n\n";
    $text .= "Berikut tagihan yang harus dibayarkan:\n\n";
    $text .= 'Nama: ' . ($message['studentName'] ?? '') . "\n";
    if (!empty($message['studentClass'])) {
        $text .= 'Kelas: ' . $message['studentClass'] . "\n";
    }
    if (!empty($message['studentNis'])) {
        $text .= 'NIS: ' . $message['studentNis'] . "\n";
    }
    if (!empty($message['vaNumber'])) {
        $text .= 'Nomor VA: ' . $message['vaNumber'] . "\n";
    }
    if (!empty($message['orderNumber'])) {
        $text .= 'No. Invoice: ' . $message['orderNumber'] . "\n";
    }
    $text .= "\nRincian Pembayaran:\n" . implode("\n", $textItems) . "\n\n";
    $text .= 'Total Tagihan: ' . format_rupiah($message['totalAmount'] ?? 0) . "\n";
    if (isset($message['outstandingAmount']) && $message['outstandingAmount'] !== null && $message['outstandingAmount'] !== '') {
        $text .= 'Outstanding / Sisa Cicilan: ' . format_rupiah($message['outstandingAmount']) . "\n";
    }
    if (!empty($message['paymentLink'])) {
        $text .= 'Link Pembayaran: ' . $message['paymentLink'] . "\n";
    }
    if (!empty($message['paymentGuide'])) {
        $text .= "\n" . $message['paymentGuide'] . "\n";
    }

    return ['html' => $html, 'text' => $text];
}
