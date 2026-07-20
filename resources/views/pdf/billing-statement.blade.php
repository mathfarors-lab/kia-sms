<!DOCTYPE html>
<html lang="km">
<head>
<meta charset="UTF-8">
<style>
@font-face {
    font-family: 'NotoKhmer';
    font-style: normal;
    font-weight: normal;
    src: url("{{ storage_path('fonts/NotoSansKhmer.ttf') }}") format('truetype');
}
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'NotoKhmer', 'DejaVu Sans', sans-serif; font-size: 11px; color: #1b1f33; line-height: 1.5; }
.page { padding: 24px 32px; }
.header { text-align: center; border-bottom: 3px solid #2B3A8F; padding-bottom: 12px; margin-bottom: 16px; }
.header h1 { font-size: 18px; color: #2B3A8F; }
.header p { font-size: 10px; color: #5b6079; margin-top: 3px; }
.student-info { display: table; width: 100%; border: 1px solid #E4E7F4; border-radius: 4px; margin-bottom: 16px; }
.si-row { display: table-row; }
.si-cell { display: table-cell; padding: 5px 10px; width: 33%; border-bottom: 1px solid #E4E7F4; border-right: 1px solid #E4E7F4; }
.si-cell:last-child { border-right: none; }
.si-label { font-size: 9px; color: #5b6079; display: block; }
.si-value { font-size: 11px; font-weight: bold; }
table { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 10px; }
thead tr { background: #2B3A8F; color: #fff; }
th { padding: 5px 8px; text-align: left; }
td { padding: 4px 8px; border-bottom: 1px solid #E4E7F4; }
tr:nth-child(even) td { background: #F6F7FC; }
.text-right { text-align: right; }
.summary-row { display: table; width: 100%; border: 1px solid #2B3A8F; border-radius: 4px; margin-top: 10px; }
.sum-cell { display: table-cell; text-align: center; padding: 6px; border-right: 1px solid #E4E7F4; font-size: 10px; }
.sum-cell:last-child { border-right: none; }
.sum-label { color: #5b6079; font-size: 8px; }
.sum-value { font-size: 14px; font-weight: bold; color: #2B3A8F; }
.footer { margin-top: 40px; display: table; width: 100%; font-size: 9px; color: #5b6079; }
.sig-cell { display: table-cell; text-align: center; width: 50%; padding-top: 24px; border-top: 1px solid #1b1f33; }
</style>
</head>
<body>
<div class="page">
    <div class="header">
        <h1>Khmer Intellectual Academy</h1>
        <p>Student Billing Statement / របាយការណ៍​វិក្កយបត្រ​សិស្ស</p>
        <p>{{ now()->format('d F Y') }}</p>
    </div>

    <div class="student-info">
        <div class="si-row">
            <div class="si-cell">
                <span class="si-label">Name (EN) / ឈ្មោះ</span>
                <span class="si-value">{{ $student->name_en }}</span>
            </div>
            <div class="si-cell">
                <span class="si-label">Name (KM) / ឈ្មោះខ្មែរ</span>
                <span class="si-value">{{ $student->name_km ?? '—' }}</span>
            </div>
            <div class="si-cell">
                <span class="si-label">Student ID / លេខ</span>
                <span class="si-value">{{ $student->student_code }}</span>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date / កាលបរិច្ឆេទ</th>
                <th>Description / សេចក្តីលម្អិត</th>
                <th class="text-right">Charge / ចំនួន​គិត​ថ្លៃ</th>
                <th class="text-right">Payment / ការទូទាត់</th>
                <th class="text-right">Balance / សមតុល្យ</th>
            </tr>
        </thead>
        <tbody>
            @forelse($ledger as $row)
            <tr>
                <td>{{ \Illuminate\Support\Carbon::parse($row['date'])->format('d M Y') }}</td>
                <td>{{ $row['description'] }}</td>
                <td class="text-right">{{ $row['type'] === 'charge' ? '$' . number_format($row['amount'], 2) : '—' }}</td>
                <td class="text-right">{{ $row['type'] === 'payment' ? '$' . number_format($row['amount'], 2) : '—' }}</td>
                <td class="text-right">${{ number_format($row['running_balance'], 2) }}</td>
            </tr>
            @empty
            <tr><td colspan="5" style="text-align:center;color:#5b6079;">No billing history on record.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="summary-row">
        <div class="sum-cell">
            <div class="sum-label">Total Charged / សរុប​គិត​ថ្លៃ</div>
            <div class="sum-value">${{ number_format($totalCharged, 2) }}</div>
        </div>
        <div class="sum-cell">
            <div class="sum-label">Total Paid / សរុប​បាន​ទូទាត់</div>
            <div class="sum-value">${{ number_format($totalPaid, 2) }}</div>
        </div>
        <div class="sum-cell">
            <div class="sum-label">Current Balance / សមតុល្យ​បច្ចុប្បន្ន</div>
            <div class="sum-value">${{ number_format($balance, 2) }}</div>
        </div>
    </div>

    <div class="footer">
        <div class="sig-cell">Accountant / គណនេយ្យករ</div>
        <div class="sig-cell">School Seal / ត្រាសាលា</div>
    </div>
</div>
</body>
</html>
