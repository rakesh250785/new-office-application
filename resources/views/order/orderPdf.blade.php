<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Order</title>
    <style>
        /* ================= FONT EMBED (PDF SAFE) ================= */
        @font-face {
            font-family: 'Calibri';
            src: url("/fonts/calibri.ttf") format("truetype");
            font-weight: normal;
            font-style: normal;
        }

        @font-face {
            font-family: 'Calibri';
            src: url("/fonts/calibri.ttf") format("truetype");
            font-weight: 700;
            font-style: normal;
        }

        @font-face {
            font-family: 'Calibri';
            src: url("/fonts/calibri.ttf") format("truetype");
            font-weight: normal;
            font-style: italic;
        }

        @font-face {
            font-family: 'Calibri';
            src: url("/fonts/calibri.ttf") format("truetype");
            font-weight: 700;
            font-style: italic;
        }

        /* ================= BASE ================= */
        body,
        table,
        th,
        td {
            font-family: "Calibri", "Arial", Arial, Helvetica, sans-serif;
            font-size: 11px;
            line-height: 1.5;
            color: #1f1f1f;
            font-variant-numeric: tabular-nums;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            background: #fff;
        }

        /* ================= PAGE ================= */
        @page {
            margin: 5mm;
        }

        .paper {
            box-sizing: border-box;
        }

        /* ================= TABLE CORE ================= */
        table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
            table-layout: auto;
        }

        /* ================= UNIFORM BORDER SYSTEM ================= */
        th,
        td {
            padding: 2px;
            vertical-align: top;
            white-space: normal;
            word-break: break-word;
            overflow-wrap: anywhere;
            box-sizing: border-box;
            border: 0.4px solid #C2C9CC;
        }

        .items tr,
        td {
            page-break-inside: auto;
        }

        /* ================= HEADER ================= */
        .hdr {
            background: #bcecfa;
            width: 100%;
            margin: 0 !important;
            padding: 0 !important;
            border: none !important;
            border-collapse: collapse;
        }

        .hdr,
        .hdr tr,
        .hdr td {
            border: none !important;
        }

        .hdr td {
            vertical-align: top;
            line-height: 1.2;
            color: #333;
        }

        .hdr img {
            display: block;
            height: 60px;
            margin: 0;
            padding: 0;
        }

        .hdr .iso,
        .hdr-iso {
            font-size: 11px;
            line-height: 1.2;
            margin-top: 2px;
        }

        .hdr p,
        .hdr div,
        .hdr span {
            margin: 0;
            padding: 0;
            line-height: 1.2;
        }

        .hdr+* {
            margin-top: 0 !important;
        }

        /* ================= META ================= */
        .meta-label {
            color: #004f6e;
            letter-spacing: 0.2px;
        }

        .meta-label.alert {
            color: #b30f16;
        }

        .meta-link {
            color: #0b57d0;
            text-decoration: underline;
        }

        /* ================= AUTHORISED ================= */
        .authorised {
            background: #2b2b2b;
            color: #f5f2e8;
            text-align: center;
            font-size: 12px;
            line-height: 1.35;
            letter-spacing: 0.25px;
            padding: 2px;
            font-weight: 700;
        }

        .authorised td {
            border: none !important;
        }


        /* ================= BASE TABLE ================= */
        .ship {
            background: #f2f8f9;
            width: 100%;
            margin-bottom: 0;
            border-collapse: collapse;
            table-layout: auto;
        }

        .ship th,
        .ship td {
            padding: 0 4px;
            vertical-align: middle;
            text-align: left;
            border: 0.4px solid #C2C9CC;
        }

        .ship th {
            font-size: 12px;
            color: #004f6e;
            white-space: nowrap;
        }

        .ship td {
            font-size: 11px;
        }

        /* BILLING */
        .ship tr.billing th,
        .ship tr.billing td {
            background-color: #CCECFF;
        }

        .ship tr.billing th {
            color: #B30F16;
            font-weight: 700;
        }

        /* SHIPPING */
        .ship tr.shipping th,
        .ship tr.shipping td {
            background-color: #99CCFF;
        }

        .ship tr.shipping th,
        td {
            border: 0.4px solid #9AA3A8;
        }

        .shipping th,
        td {
            border: 0.4px solid #9AA3A8;
        }

        .ship tr.topinfo:last-of-type th,
        .ship tr.topinfo:last-of-type td {
            border-bottom: none;
        }

        .ship tr.billing:last-of-type th,
        .ship tr.billing:last-of-type td {
            border-bottom: none;
        }

        /* ================= ITEMS ================= */
        .items {
            margin-bottom: 7px;
            margin-top: 6px;
        }

        .items th {
            background: #eef6f9;
            color: #004f6e;
            font-size: 12px;
            text-align: left;
        }

        /* ================= TOTAL ROW ================= */
        .items tbody tr.total-row td {
            background: #333 !important;
            color: #f5f2e8;
            font-size: 14px;
            letter-spacing: 0.3px;
            font-weight: 700;
            border: none !important;
            border-bottom: 0.5px solid #C2C9CC !important;
        }

        .total-row .label {
            text-align: center;
        }


        /* ================= HTML EDITOR HARD RESET ================= */
        .spec-row td,
        .comment-row td,
        .notes td,
        .items td.description,
        .items td.heading,
        .items td.html-content {
            line-height: 1.2 !important;
        }

        .spec-row td p,
        .comment-row td p,
        .notes td p,
        .items td.description p,
        .items td.heading p,
        .items td.html-content p {
            margin: 0 !important;
            padding: 0 !important;
        }

        .spec-row td p:empty,
        .comment-row td p:empty,
        .notes td p:empty,
        .items td div:empty {
            display: none !important;
        }

        .spec-row td br,
        .comment-row td br,
        .notes td br,
        .items td.description br,
        .items td.heading br,
        .items td.html-content br {
            display: none !important;
        }

        .spec-row td div,
        .comment-row td div,
        .notes td div,
        .items td.description div,
        .items td.heading div,
        .items td.html-content div,
        .spec-row td span,
        .comment-row td span {
            margin: 0 !important;
            padding: 0 !important;
            line-height: 1.2 !important;
        }

        .spec-row td ul,
        .spec-row td ol,
        .notes td ul,
        .notes td ol,
        .comment-row td ul,
        .comment-row td ol {
            margin: 0 !important;
            padding-left: 14px !important;
        }

        .spec-row td li,
        .notes td li,
        .comment-row td li {
            margin: 0 !important;
            padding: 0 !important;
            line-height: 1 !important;
        }

        .spec-row td,
        .notes td,
        .comment-row td {
            padding-top: 2px !important;
            padding-bottom: 2px !important;
        }

        .items thead {
            display: table-row-group;
        }

        /* ===== MONEY COLUMNS — NEVER WRAP ===== */
        .items th.money,
        .items td.money,
        .items .total-row td {
            white-space: nowrap !important;
            word-break: keep-all !important;
            overflow-wrap: normal !important;
            text-align: center;
            padding: 4px !important;
            font-variant-numeric: tabular-nums;
        }


        /* ================= FOOTER / TERMS ================= */
        .footer-title {
            color: #004f6e;
            font-size: 13.2px;
            letter-spacing: 0.2px;
        }

        .terms,
        .terms * {
            font-size: 12px;
            line-height: 1.35 !important;
            color: #2b2b2b;
            font-weight: 700;
        }

        .terms p,
        .terms li {
            margin: 0 !important;
            padding: 0 !important;
        }

        .terms ul,
        .terms ol {
            margin: 0 !important;
            padding-left: 14px !important;
        }

        .terms br {
            display: none;
        }

        /* ===== MONEY COLUMNS — NEVER WRAP ===== */
        .items th.money,
        .items td.money,
        .items .total-row td {
            white-space: nowrap !important;
            word-break: keep-all !important;
            overflow-wrap: normal !important;
            text-align: center;
            padding: 4px !important;
            font-variant-numeric: tabular-nums;
        }

        .html-content ol li[data-list="ordered"] {
            list-style-type: decimal !important;
            list-style-position: outside !important;
            padding-left: 0px !important;
            margin: 0;
            font-weight: 700;
            margin-left: 10px;
        }

        .html-content ol li[data-list="bullet"] {
            list-style-type: disc !important;
            list-style-position: outside !important;
            padding-left: 0px !important;
            margin: 0;
            font-weight: 700;
            margin-left: 10px;
        }

        .html-content li {
            margin-bottom: 4px;
        }


        .items th.money,
        .items td.money {
            white-space: nowrap;
            text-align: right;
            padding: 4px;
        }

        .items th.money {
            white-space: nowrap;
            text-align: left;
            padding: 4px;
            font-size: 11px;
        }

        .items th.money-real,
        .items td.money-real {
            white-space: nowrap;
            text-align: right;
            padding: 4px;
        }

        .items th.money-real {
            white-space: nowrap;
            text-align: right;
            padding: 4px;
            font-size: 11px;
        }

        .total-row th.money-real,
        .items td.money-real {
            white-space: nowrap;
            text-align: right;
            padding: 4px;
        }


        .label-heading {
            display: flex;
            flex-direction: column;
            font-size: 10px;
            font-weight: 700;
            line-height: 1;
            color: #006c95;
            margin-bottom: -15px;
        }

        .detail-text {
            font-size: 10px;
            line-height: 1;
            margin: 0;
            padding: 0;
            white-space: normal;
        }

        .ql-container,
        .ql-editor {
            background: #ffffff !important;
            color: #000;
        }
    </style>
</head>

<body>

    @php
        $currency = $currency->name ?? '';
        $isMH = ($shipping['state'] ?? '') === 'Maharashtra';
        $isGW = ($quotationInfo['quotation_type'] ?? '') === 'GW';

    @endphp

    @php
        $isGW = ($quotationInfo['quotation_type'] === 'GW');
        $isMH = ($shipping['state'] === 'Maharashtra');

        if (!$isGW && $isMH) {
            $noteColspan = 14;
        } else if (!$isGW && !$isMH) {
            $noteColspan = 12;
        } else if ($isGW && !$isMH) {
            $noteColspan = 14;
        } elseif ($isGW && $isMH) {
            $noteColspan = 16;
        }

    @endphp

    <div class="paper">

        {{-- ================= HEADER ================= --}}
        <table class="hdr" width="100%" cellpadding="0" cellspacing="0" style="
    border-collapse:collapse;
    width:100%;
    background:#cfeef9;
">
            <tr>
                <!-- LEFT : LOGO + ISO -->
                <td style="
            width:18%;
            text-align:center;
            vertical-align:top;
            padding:6px 4px;
              
        ">
                    @if(!empty($company['logo']))
                        <img src="{{ $company['logo'] }}"
                            style="max-width:200px; height:auto; display:block; margin:0 auto;margin-top: -17px">
                    @else
                        <div style="font-weight:700; font-size:14px;">Order</div>
                    @endif

                    <div style="
                        margin-top: 0px;
                        font-size:12px;
                        font-weight:700;
                        color:#111;
                    ">
                        ISO 9001-2015
                    </div>
                </td>

                <!-- RIGHT : DETAILS -->
                <td style="
                    vertical-align: top;
                    padding: 6px 8px;
                    font-size: 12px;          /* content size */
                    line-height: 1.5;
                    line-spacing: 1.5;
                    color: #111;
                    font-weight: 700;
                ">
                    <!-- 1️⃣ FIRST LINE : ADDRESS -->
                    <b style="font-size:12px;color:#004f6e;">Address :</b>
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 217, 2nd Floor, Champaklal Industrial Estate, Sion East,
                    Mumbai – 400022, India
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <b style="font-size:11px;color:#004f6e;">Call :</b>
                    &nbsp;&nbsp; 91-022-43159100
                    <br>

                    <!-- 2️⃣ SECOND LINE : EMAILS + CALL -->
                    <b style="font-size:12px;color:#004f6e;">Email :</b>
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    sales@chromatographyworld.com,&nbsp;&nbsp;&nbsp;&nbsp;
                    speed@chromatographyworld.com,&nbsp;&nbsp;&nbsp;&nbsp;
                    gm-support@chromatographyworld.com
                    &nbsp;&nbsp;

                    <br>

                    <!-- 3️⃣ THIRD LINE : GST + MSME + WEBSITE -->
                    <b style="font-size:12px;color:#800000;">GSTN :</b>
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 27AAGFC1217K1ZM
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <b style="font-size:12px;color:#800000;">Udyam / MSME :</b>
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; UDYAM-MH-19-0078510
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <b style="font-size:12px;color:#004f6e;">Web :</b>
                    <span style="color:#0b57d0;">&nbsp;&nbsp;&nbsp;&nbsp; www.chromatographyworld.com</span>
                    <br>

                    <!-- 4️⃣ FOURTH LINE : BANK DETAILS -->
                    <b style="font-size:12px;color:#004f6e;">Bank :</b>
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Kotak Mahindra Bank
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <b style="font-size:12px;color:#004f6e;">Branch :</b>
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Matunga
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <b style="font-size:12px;color:#004f6e;">IFSC :</b>
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; KKBK0000644
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <b style="font-size:12px;color:#004f6e;">A/C :</b>
                    &nbsp;&nbsp;&nbsp;&nbsp; 4611234274
                </td>
            </tr>
        </table>


        {{-- AUTHORISED --}}
        <div class="authorised">
            <b>Authorised For :</b>
            Qualisil, Qaliseal, Gas World, Macherey Nagel,
            G.L.Science (GC Columns), S.A.S.Corporation,
            Nomura Chemicals (Develosil), Sielc (Primesep),
            Sciencix, Poly LC, MZAnalysentechnik, Sepax, Frontier Lab.
            Click <b style="
              color: #fff2cc;
              text-decoration: underline;
              font-size: 14px;
            ">
                www.chromatographyworld.com
            </b>
            for more details.
        </div>

        <h4 style="
            text-align:center;
            color:#800000;
            font-size:18px;
            font-weight:700;
            margin:2px 0;
            vertical-align: middle;
            letter-spacing:0.3px;
        ">
            Purchase Order Acknowledgement
        </h4>

        <table class="ship">
            <colgroup>
                <col span="8">
            </colgroup>
            <tbody>
                <tr class="topinfo">
                    <th>Customer Order</th>
                    <td>{{ $orderInfo['customer_order_no'] }}</td>

                    <th>Order Date</th>
                    <td colspan="2">{{ $orderInfo['order_date'] }}</td>

                    <th>Quotation No.</th>
                    <td colspan="2">{{ $quotationInfo['unique_quotation_no'] }}</td>

                    <th>Quotation Date</th>
                    <td colspan="2">{{ $quotationInfo['date'] }}</td>
                </tr>

                <tr class="topinfo">
                    <th>Order Ref.</th>
                    <td>{{ $orderInfo['ref'] }}</td>

                    <th>Date</th>
                    <td>{{ $orderInfo['date'] }}</td>

                    <th>Credit Terms</th>
                    <td colspan="3">{{ $delivery_term_data }}</td>

                    <th>Preferred Courier</th>
                    <td colspan="2">{{ $courier_name }}</td>
                </tr>
            </tbody>
        </table>

        <table class="ship">
            <colgroup>
                <col span="8">
            </colgroup>
            <tbody>
                <tr class="billing">
                    <th>Billing Name</th>
                    <td colspan="6">{{ $billing['company'] }}</td>
                    <th>GSTN</th>
                    <td colspan="3">{{ $billing['gstn'] }}</td>
                </tr>

                <tr class="billing">
                    <th>Billing Address</th>
                    <td colspan="2">{{ $billing['address'] }}</td>
                    <th>Pin Code</th>
                    <td>{{ $billing['pincode'] }}</td>
                    <th>City</th>
                    <td>{{ $billing['city'] }}</td>
                    <th>State</th>
                    <td>{{ $billing['state'] }}</td>
                    <th>Country</th>
                    <td>{{ $billing['country'] }}</td>
                </tr>

                <tr class="billing">
                    <th>Contact Person</th>
                    <td>{{ $billing['contact_person'] }}</td>
                    <th>Email</th>
                    <td colspan="2">{{ $billing['email'] }}</td>
                    <th>Phone</th>
                    <td colspan="2">{{ $billing['landline'] }}</td>
                    <th>Mobile</th>
                    <td colspan="2">{{ $billing['mobile'] }}</td>
                </tr>
            </tbody>
        </table>

        <table class="ship">
            <colgroup>
                <col span="8">
            </colgroup>
            <tbody>
                <tr class="shipping" style="border: 0.4px solid #9AA3A8">
                    <th style="border: 0.4px solid #9AA3A8">Shipping Name</th>
                    <td style="border: 0.4px solid #9AA3A8" colspan="6">{{ $shipping['company'] }}</td>
                    <th style="border: 0.4px solid #9AA3A8">GSTN</th>
                    <td style="border: 0.4px solid #9AA3A8" colspan="3">{{ $shipping['gstn'] }}</td>
                </tr>

                <tr class="shipping" style="border: 0.4px solid #9AA3A8">
                    <th style="border: 0.4px solid #9AA3A8">Shipping Address</th>
                    <td style="border: 0.4px solid #9AA3A8" colspan="2">{{ $shipping['address'] }}</td>
                    <th style="border: 0.4px solid #9AA3A8">Pin Code</th>
                    <td style="border: 0.4px solid #9AA3A8">{{ $shipping['pincode'] }}</td>
                    <th style="border: 0.4px solid #9AA3A8">City</th>
                    <td style="border: 0.4px solid #9AA3A8">{{ $shipping['city'] }}</td>
                    <th style="border: 0.4px solid #9AA3A8">State</th>
                    <td style="border: 0.4px solid #9AA3A8">{{ $shipping['state'] }}</td>
                    <th style="border: 0.4px solid #9AA3A8">Country</th>
                    <td style="border: 0.4px solid #9AA3A8">{{ $shipping['country'] }}</td>
                </tr>

                <tr class="shipping" style="border: 0.4px solid #9AA3A8">
                    <th style="border: 0.4px solid #9AA3A8">Contact Person</th>
                    <td style="border: 0.4px solid #9AA3A8">{{ $shipping['contact_person'] }}</td>
                    <th style="border: 0.4px solid #9AA3A8">Email</th>
                    <td style="border: 0.4px solid #9AA3A8" colspan="2">{{ $shipping['email'] }}</td>
                    <th style="border: 0.4px solid #9AA3A8">Phone</th>
                    <td style="border: 0.4px solid #9AA3A8" colspan="2">{{ $shipping['landline'] }}</td>
                    <th style="border: 0.4px solid #9AA3A8">Mobile</th>
                    <td style="border: 0.4px solid #9AA3A8" colspan="2">{{ $shipping['mobile'] }}</td>
                </tr>
            </tbody>
        </table>

        {{-- ================= ITEMS ================= --}}
        <table class="items">
            <thead>
                <tr>
                    <th class="money">Sr</th>
                    <th class="money">Part No.</th>
                    <th class="money">Description</th>

                    @if($isGW)
                        <th class="money">Maker</th>
                        <th class="money">UOM</th>
                    @endif

                    <th class="money">HSN</th>
                    <th class="money">Qty</th>
                    <th class="money-real">Unit Price ({{ $currency }})</th>
                    <th class="money-real">Disc %</th>
                    <th class="money-real">Net Price ({{ $currency }})</th>

                    @if($isMH)
                        <th class="money-real">SGST %</th>
                        <th class="money-real">SGST AMT</th>
                        <th class="money-real">CGST %</th>
                        <th class="money-real">CGST AMT</th>
                    @else
                        <th class="money-real">IGST %</th>
                        <th class="money-real">IGST AMT</th>
                    @endif

                    <th class="money-real">Total ({{ $currency }})</th>
                    <th class="money">Delivery</th>
                </tr>
            </thead>

            <tbody>
                @php
                    $hasText = function ($v) {
                        return !empty(trim(preg_replace('/<[^>]*>|&nbsp;/', '', $v ?? '')));
                    };
                @endphp
                @foreach($items as $i => $it)
                            @php
                                $net = (float) $it['net_price'];
                                $igst = (float) $it['igst'];
                                $half = $igst / 2;
                                $igstAmt = $net * $igst / 100;
                                $halfAmt = $net * $half / 100;
                                $rowTotal = $isMH ? $net + $halfAmt + $halfAmt : $net + $igstAmt;
                                $rowClass = ($i % 2 === 0) ? 'odd' : 'even';
                                $showDetails =
                                    ($isGW && ($hasText($it['specification'] ?? null)))
                                    || $hasText($it['product_specification'] ?? null);
                            @endphp


                            <tr class="item-row {{ $rowClass }}" style="background-color: {{ 
                                                                                                                                                                                            $isGW == 'GW'
                    ? ($loop->index % 2 === 0 ? '#C1D9BF' : '#D9E9D6')
                    : ($loop->index % 2 === 0 ? '#faeff0' : '#f1d3d6')

                                                                                                                    }};">

                                <td>{{ $i + 1 }}</td>
                                <td class="money">{{ $it['part_no'] }}</td>
                                <td>{{$it['description'] }}</td>

                                @if($isGW)
                                    <td class="money">{{ $it['principal']['type'] ?? $it['principal'] ?? '' }}</td>
                                    <td class="money">
                                        {{ is_array($it['uom'] ?? null) ? ($it['uom']['uom'] ?? '') : ($it['uom'] ?? '') }}</td>
                                @endif

                                <td class="money">{{ $it['hsn_code'] }}</td>
                                <td>{{ $it['quantity'] }}</td>
                                <td class="money-real">{{ number_format($it['price'], 2) }}</td>
                                <td class="money-real">{{ $it['discount'] }}</td>
                                <td class="money-real">{{ number_format($net, 2) }}</td>

                                @if($isMH)
                                    <td class="money-real">{{ number_format($half, 2) }}</td>
                                    <td class="money-real">{{ number_format($halfAmt, 2) }}</td>
                                    <td class="money-real">{{ number_format($half, 2) }}</td>
                                    <td class="money-real">{{ number_format($halfAmt, 2) }}</td>
                                @else
                                    <td class="money-real">{{ number_format($igst, 2) }}</td>
                                    <td class="money-real">{{ number_format($igstAmt, 2) }}</td>
                                @endif

                                <td class="money-real">{{ number_format($rowTotal, 2) }}</td>
                                <td class="notes">{!! $it['notes'] ?? '' !!}</td>
                            </tr>

                            @if($showDetails)
                                <tr class="item-row {{ $rowClass }}"
                                    style="background-color: {{ $loop->index % 2 === 0 ? '#faeff0' : '#f1d3d6' }};">
                                    <td style="background-color: {{ $isGW == 'GW' ? '#f1d3d6' : 'transparent' }};"></td>
                                    <td colspan="{{ $noteColspan - 1 }}"
                                        style="padding:2px 2px; background-color: {{ $isGW == 'GW' ? '#f1d3d6' : 'transparent' }};">
                                        <span style="display:flex; flex-direction:column; gap:2px;font-size:10px">
                                            @if($hasText($it['product_specification'] ?? null))
                                                <span class="detail-text  html-content">
                                                    <b class="label-heading">
                                                        Comments:
                                                    </b>&nbsp;
                                                    <span>{!! $it['product_specification'] !!}</span>
                                                </span>
                                            @endif
                                            @if($isGW && $hasText($it['specification'] ?? null))
                                                <span class="detail-text  html-content">
                                                    <b class="label-heading">
                                                        Specification with Heading:
                                                    </b>&nbsp;
                                                    <span>{!! $it['specification'] !!}</span>

                                                </span>
                                            @endif
                                        </span>
                                    </td>
                                </tr>
                            @endif
                @endforeach

                {{-- TOTAL --}}
                <tr class="total-row">
                    <td colspan="{{ $isGW ? 9 : 7 }}" class="label">
                        Grand Total ({{ $currency }})
                    </td>

                    <td class="money-real">{{ number_format($totals['sub_net_total'], 2) }}</td>

                    @if($isMH)
                        <td></td>
                        <td class="money">{{ number_format(($totals['grand_total'] - $totals['sub_net_total']) / 2, 2) }}
                        </td>
                        <td></td>
                        <td class="money">{{ number_format(($totals['grand_total'] - $totals['sub_net_total']) / 2, 2) }}
                        </td>
                    @else
                        <td></td>
                        <td class="money">{{ number_format($totals['total_igst_total'], 2) }}</td>
                    @endif

                    <td class="money">{{ number_format($totals['grand_total'], 2) }}</td>
                    <td></td>
                </tr>

                @if(!empty($product_description))
                    <tr style="background-color: #C9F1FF">
                        <td colspan="{{ $noteColspan }}" style="border:none; padding:6px 4px;">
                            <b>NOTES :</b> {!! $product_description!!}
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>

        {{-- ================= FOOTER ================= --}}
        <table width="100%" cellpadding="0" cellspacing="0" style="
            border-collapse:collapse;
            border:0;
            outline:0;
            box-shadow:none;
            font-size:10px;
            margin-top:6px;

            background-image: url('{{ $term_conditon_bg_img }}');
            background-repeat: no-repeat;
            background-position: center;
            background-size: 100% 100%;
        ">

            <!-- TERMS + SIGNATORY -->
            <tr style="border:0;">
                <!-- LEFT : TERMS -->
                <td width="72%" valign="top" style="
                        padding:5px 7px;
                        line-height:1.25;
                        background: transparent;
                        border:0;
                        ">

                    <div style="
                        font-weight:700;
                        color:#004f6e;
                        margin-bottom:3px;
                        font-size:12px;
                    ">
                        Terms & Conditions:
                    </div>

                    <div style="line-height:1.25; border:0;">
                        <div class="terms" style="border:0;">
                            {!! $terms !!}
                        </div>
                    </div>
                </td>

                <!-- RIGHT : SIGNATORY -->
                <td width="28%" valign="top" style="
                    padding:5px 7px;
                    text-align:right;
                    line-height:1.25;
                    background: transparent;
                    border:0;
                    font-weight: 700;
                    font-size:12px;
                    ">
                    <div style="font-weight:700; color:#004f6e;">
                        For Chromatography World
                    </div>

                    <div style="font-weight:700; color:#004f6e; margin-top:4px;">
                        Authorized Signatory
                    </div>

                    <div style="margin-top:6px; font-weight:600;">
                        <u>{{ $prepared_by }}</u>
                    </div>
                </td>
            </tr>



        </table>
        <table width="100%" cellpadding="0" cellspacing="0" style="
        border-collapse:collapse;
        border:0;
        outline:0;
        box-shadow:none;
    
        font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
        font-size:10px;
        margin-top:6px;
    
        background-repeat: no-repeat;
        background-position: center;
        background-size: 100% 100%;
    ">

            <!-- FOOT STRIP -->
            <tr style="border:0;">
                <td colspan="2" style="
                background:#c9f1ff;
                text-align:center;
                padding:6px 8px;
                font-size:13px;
                font-weight:700;
                border:0;
            ">
                    <div style="margin-bottom:2px;">
                        {{ $branch_address }}
                    </div>

                    <div style="
                    color:maroon;
                    font-size:13px;
                    font-weight:700;
                ">
                        We Look Forward To Your Valuable Order! &nbsp; Thank You!
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>