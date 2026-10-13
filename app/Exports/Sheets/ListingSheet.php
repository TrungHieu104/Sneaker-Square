<?php

namespace App\Exports\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * The layout every spreadsheet the admin downloads shares: a merged title
 * row, a grey header row, then one numbered row per record.
 *
 * A sheet only says what it lists and how one record reads as a row.
 */
abstract class ListingSheet extends DefaultValueBinder implements FromCollection, ShouldAutoSize, WithCustomValueBinder, WithEvents, WithHeadings, WithMapping, WithStyles
{
    private int $number = 1;

    abstract protected function heading(): string;

    /**
     * @return list<string> the headers after the leading number column
     */
    abstract protected function columns(): array;

    abstract protected function records(): Collection;

    /**
     * @return list<mixed>
     */
    abstract protected function row(mixed $record): array;

    /**
     * The tab name, when the file has more than one sheet.
     */
    protected function title(): ?string
    {
        return null;
    }

    /**
     * Headers of the columns that hold codes rather than quantities. Excel
     * would otherwise turn an all-digit code into a number, and only when it
     * happens not to start with a zero.
     *
     * @return list<string>
     */
    protected function textColumns(): array
    {
        return [];
    }

    protected function numberHeader(): string
    {
        return 'Số thứ tự';
    }

    /**
     * Rows written under the table once it is complete, such as totals.
     *
     * @return list<list<mixed>>
     */
    protected function footer(): array
    {
        return [];
    }

    public function collection(): Collection
    {
        return $this->records();
    }

    public function headings(): array
    {
        return [[$this->heading()], [$this->numberHeader(), ...$this->columns()]];
    }

    public function map($record): array
    {
        return [$this->number++, ...$this->row($record)];
    }

    public function styles(Worksheet $sheet): array
    {
        $titleRow = 'A1:'.Coordinate::stringFromColumnIndex(count($this->columns()) + 1).'1';
        $sheet->mergeCells($titleRow);
        $sheet->getStyle($titleRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        return [
            2 => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFC0C0C0']],
            ],
        ];
    }

    public function bindValue(Cell $cell, mixed $value): bool
    {
        if ($cell->getRow() > 2 && is_string($value) && in_array($cell->getColumn(), $this->textColumnLetters(), true)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    /**
     * @return list<string>
     */
    private function textColumnLetters(): array
    {
        $columns = $this->columns();

        return array_values(array_map(
            fn (string $header) => Coordinate::stringFromColumnIndex(array_search($header, $columns, true) + 2),
            array_filter($this->textColumns(), fn (string $header) => in_array($header, $columns, true)),
        ));
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                if ($title = $this->title()) {
                    $event->sheet->setTitle($title);
                }

                if ($footer = $this->footer()) {
                    $event->sheet->append([[''], ...$footer]);
                }
            },
        ];
    }
}
