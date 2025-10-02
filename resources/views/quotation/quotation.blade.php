<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Quotation</title>

  <style>
    /* Paste your printCss() content here (slightly adapted to Blade/PHP) */
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=Fira+Code:wght@400;500;700&display=swap');

    @page {
      size: A4 portrait;
      margin: 18mm; /* reduces DOMPDF default margin issues */
    }

    :root {
      --accent: #f97316;
      --muted: #6b7280;
      --title: #0b1220;
      --dark: #0f172a;
    }
    body {
      font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, Arial;
      margin: 0;
      color: var(--title);
      font-size: 12px;
    }

  </style>
</head>
<body>
  <div class="qp-print-area">
    <div style="width:100%; background:#fff; padding:12px; box-sizing:border-box; border-radius:6px;">
      <!-- Title -->
      <div style="text-align:center; color:var(--accent); font-weight:800; font-size:18px; margin-bottom:8px;">
        Quotation
      </div>

      <!-- Header grid -->
      <div class="qp-header-grid" style="display:grid; grid-template-columns:1fr 1fr 240px; gap:8px; align-items:start;">
        <div style="display:flex; gap:8px; align-items:flex-start;">
          <div style="width:56px; height:56px; border-radius:8px; background:var(--dark); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:14px; flex-shrink:0;">
            @if(!empty($headerLogoUrl))
              <img src="{{ $headerLogoUrl }}" alt="logo" style="width:100%;height:100%;object-fit:contain;border-radius:8px;" />
            @else
              CW
            @endif
          </div>

          <div style="min-width:0;">
            <div style="font-weight:800; font-size:13px; color:var(--title);">
              Chromatography World
            </div>

            <div style="margin-top:3px; color:var(--muted); font-size:11px; line-height:1.2;">
              <strong style="color:var(--title);">Address:</strong>
              <span class="mono"> 217, 2nd Floor, Champaklal Industrial Estate, Sion East, Mumbai - 400022. India</span>
            </div>

            <div style="margin-top:6px; display:flex; flex-direction:column; gap:6px;">
              <div style="font-size:11px; color:var(--muted);">
                <strong style="color:var(--title);">Contact:</strong>
                <span class="mono"> +91 - 022 - 43159100</span>
              </div>
              <div style="font-size:11px; color:var(--muted); word-break:break-word;">
                <strong style="color:var(--title);">Email:</strong>
                <span class="mono"> sales@chromatographyworld.com, speed@chromatographyworld.com</span>
              </div>
            </div>
          </div>
        </div>

        <!-- middle and right blocks (copy the structure from your React markup, using Blade) -->
        <!-- ... copy the rest of the header, shipping block, items table, totals, terms, notes and signature ... -->

        {{-- Items table --}}
        <div style="margin-top:10px;">
          <div class="qp-table-container">
            <table class="qp-table" style="width:100%; border-collapse:collapse; font-size:11px; table-layout:auto;">
              <thead>
                <tr>
                  <th style="width:34px;">#</th>
                  <th style="width:140px;">Part No.</th>
                  <th style="width:80px;">HSN</th>
                  <th style="width:46px; text-align:center;">Qty</th>
                  <th style="width:76px; text-align:right;">Rate</th>
                  <th style="width:50px; text-align:center;">Disc%</th>
                  <th style="width:90px; text-align:right;">Net</th>
                  <th style="width:50px; text-align:center;">IGST%</th>
                  <th style="width:84px; text-align:right;">IGST Amt</th>
                  <th style="width:90px; text-align:right;">Amount</th>
                  <th style="width:110px; text-align:center;">Delivery</th>
                </tr>
              </thead>

              <tbody>
                @if(empty($productRows))
                  <tr>
                    <td colspan="11" style="text-align:center; padding:6px;" class="muted">No items added</td>
                  </tr>
                @else
                  @foreach($productRows as $idx => $r)
                    @php
                      $qty = (float) ($r['quantity'] ?? 0);
                      $price = (float) ($r['price'] ?? 0);
                      $disc = (float) ($r['discount'] ?? 0);
                      $subtotalRow = $qty * $price;
                      $discAmt = $subtotalRow * ($disc/100);
                      $net = $subtotalRow - $discAmt;
                      $igst = (float) ($r['igst'] ?? 0);
                      $igstAmt = $net * ($igst/100);
                      $total = $net + $igstAmt;
                    @endphp

                    <tr class="main-row" style="background: {{ $idx % 2 ? 'rgba(249,243,238,0.45)' : '#fff' }};">
                      <td style="text-align:center; width:34px;">{{ $idx + 1 }}</td>
                      <td style="padding:4px; width:130px;">{{ $r['part_no'] ?? '' }}</td>
                      <td style="padding:4px; width:80px;">{{ $r['hsn_code'] ?? '' }}</td>
                      <td class="num mono" style="text-align:center; width:46px;">{{ number_format($qty, 2) }}</td>
                      <td class="num mono" style="text-align:right; width:76px;">{{ number_format($price, 2) }}</td>
                      <td class="num mono" style="text-align:center; width:50px;">{{ number_format($disc, 2) }}</td>
                      <td class="num mono" style="text-align:right; width:90px;">{{ number_format($net, 2) }}</td>
                      <td class="num mono" style="text-align:center; width:50px;">{{ number_format($igst, 2) }}</td>
                      <td class="num mono" style="text-align:right; width:84px;">{{ number_format($igstAmt, 2) }}</td>
                      <td class="num mono" style="text-align:right; width:90px;">{{ number_format($total, 2) }}</td>
                      <td style="padding:4px; width:110px;">{{ $r['notes'] ?? '' }}</td>
                    </tr>

                    {{-- optional description row --}}
                    @if(!empty(trim($r['description'] ?? '')))
                      <tr style="background: {{ $idx % 2 ? 'rgba(249,243,238,0.35)' : '#fbfbfb' }};">
                        <td colspan="11" style="padding:6px 8px; font-size:11px; color:var(--muted); line-height:1.25; text-align:justify;">
                          <div>
                            <p style="margin:0;"><strong style="color:var(--title);">Description:</strong> {!! nl2br(e($r['description'])) !!}</p>
                          </div>
                        </td>
                      </tr>
                    @endif
                  @endforeach
                @endif
              </tbody>
            </table>
          </div>
        </div>

        {{-- Terms + totals etc... copy the layout from React markup and convert expressions to Blade --}}
      </div>
    </div>
  </div>
</body>
</html>
