<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Report;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

class ReportController extends Controller
{
    public function exportExcel(): void
    {
        $filters = Report::buildFilters([
            'range_type' => $this->request->input('range_type', 'month'),
            'day' => $this->request->input('day', date('Y-m-d')),
            'month' => $this->request->input('month', date('Y-m')),
            'year' => $this->request->input('year', date('Y')),
            'start_date' => $this->request->input('start_date', date('Y-m-01')),
            'end_date' => $this->request->input('end_date', date('Y-m-d')),
        ]);

        $report = Report::fetchExportRows($filters);
        $rows = $report['rows'];
        $totals = $report['totals'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Bao cao tai chinh');

        $sheet->setCellValue('A1', 'Ngay ban');
        $sheet->setCellValue('B1', 'Ma don hang');
        $sheet->setCellValue('C1', 'Ten san pham');
        $sheet->setCellValue('D1', 'So luong');
        $sheet->setCellValue('E1', 'Gia nhap');
        $sheet->setCellValue('F1', 'Gia ban');
        $sheet->setCellValue('G1', 'Thue VAT');
        $sheet->setCellValue('H1', 'Thue nhap khau');
        $sheet->setCellValue('I1', 'Loi nhuan (%)');
        $sheet->setCellValue('J1', 'Giam gia');
        $sheet->setCellValue('K1', 'Loi nhuan thuc te');
        $sheet->setCellValue('L1', 'Doanh thu san pham');

        $sheet->getStyle('A1:L1')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A1:L1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB('FFE9ECEF');

        $rowIndex = 2;
        foreach ($rows as $row) {
            $quantity = (int)($row['quantity'] ?? 0);
            $costPrice = (int)($row['cost_price'] ?? 0);
            $sellingPrice = (int)($row['selling_price'] ?? 0);
            $vatTax = (int)round((float)($row['vat_tax'] ?? 0));
            $importTax = (int)round((float)($row['import_tax'] ?? 0));
            $profitPercent = (float)($row['profit_percent'] ?? 0);
            $discountAmount = (int)round((float)($row['discount_amount'] ?? 0));
            $profitAmount = (int)($row['profit_amount'] ?? 0);
            $productRevenue = (int)($row['product_revenue'] ?? 0);

            $sheet->setCellValue('A' . $rowIndex, (string)($row['sale_datetime'] ?? ''));
            $sheet->setCellValue('B' . $rowIndex, (int)($row['order_id'] ?? 0));
            $sheet->setCellValue('C' . $rowIndex, (string)($row['product_name'] ?? ''));
            $sheet->setCellValue('D' . $rowIndex, $quantity);
            $sheet->setCellValue('E' . $rowIndex, $costPrice);
            $sheet->setCellValue('F' . $rowIndex, $sellingPrice);
            $sheet->setCellValue('G' . $rowIndex, $vatTax);
            $sheet->setCellValue('H' . $rowIndex, $importTax);
            $sheet->setCellValue('I' . $rowIndex, $profitPercent);
            $sheet->setCellValue('J' . $rowIndex, $discountAmount);
            $sheet->setCellValue('K' . $rowIndex, $profitAmount);
            $sheet->setCellValue('L' . $rowIndex, $productRevenue);

            $rowIndex++;
        }

        $summaryStart = $rowIndex + 1;
        $sheet->setCellValue('A' . $summaryStart, 'Tong ket');
        $sheet->mergeCells('A' . $summaryStart . ':B' . $summaryStart);
        $sheet->getStyle('A' . $summaryStart . ':B' . $summaryStart)->getFont()->setBold(true);

        $sheet->setCellValue('A' . ($summaryStart + 1), 'Tong doanh thu');
        $sheet->setCellValue('B' . ($summaryStart + 1), (int)($totals['total_revenue'] ?? 0));

        $sheet->setCellValue('A' . ($summaryStart + 2), 'Tong chi phi');
        $sheet->setCellValue('B' . ($summaryStart + 2), (int)($totals['total_cost'] ?? 0));

        $sheet->setCellValue('A' . ($summaryStart + 3), 'Tong giam gia');
        $sheet->setCellValue('B' . ($summaryStart + 3), (int)($totals['total_discount'] ?? 0));

        $sheet->setCellValue('A' . ($summaryStart + 4), 'Tong thue');
        $sheet->setCellValue('B' . ($summaryStart + 4), (int)($totals['total_tax'] ?? 0));

        $sheet->setCellValue('A' . ($summaryStart + 5), 'Tong loi nhuan');
        $sheet->setCellValue('B' . ($summaryStart + 5), (int)($totals['total_profit'] ?? 0));

        $sheet->getStyle('A' . ($summaryStart + 1) . ':B' . ($summaryStart + 5))->getFont()->setBold(true);
        $sheet->getStyle('B' . ($summaryStart + 1) . ':B' . ($summaryStart + 5))
            ->getNumberFormat()
            ->setFormatCode('#,##0');

        $moneyColumns = ['E', 'F', 'G', 'H', 'J', 'K', 'L'];
        foreach ($moneyColumns as $col) {
            $sheet->getStyle($col . '2:' . $col . max(2, $rowIndex - 1))
                ->getNumberFormat()
                ->setFormatCode('#,##0');
        }
        $sheet->getStyle('I2:I' . max(2, $rowIndex - 1))
            ->getNumberFormat()
            ->setFormatCode('0.00"%"');

        $sheet->getStyle('A1:L' . max(1, $rowIndex - 1))
            ->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER);

        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'bao_cao_tai_chinh_' . date('Ymd_His') . '.xls';

        if (ob_get_length()) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xls($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}