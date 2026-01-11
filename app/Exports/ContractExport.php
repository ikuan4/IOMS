<?php

namespace App\Exports;

use App\Models\Contract;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ContractExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles
{
    protected $contract;

    public function __construct(Contract $contract)
    {
        $this->contract = $contract;
    }

    public function collection()
    {
        // Return the contract with all its versions
        $this->contract->load([
            'branch',
            'contractType',
            'creator',
            'updater',
            'versions',
            'reminders',
            'notificationRecipients'
        ]);

        // Return single item collection
        return collect([$this->contract]);
    }

    public function headings(): array
    {
        return [
            'Contract Number',
            'Branch',
            'Contract Type',
            'Contract With',
            'Grace Period (Days)',
            'Status',
            'Is Active',
            'Latest Version',
            'Start Date (IST)',
            'End Date (IST)',
            'Description',
            'Total Versions',
            'Reminders (Days Before)',
            'Notification Recipients',
            'Created By',
            'Created At',
            'Updated By',
            'Updated At',
        ];
    }

    public function map($contract): array
    {
        $latestVersion = $contract->latestVersion;
        
        $startDate = $latestVersion ? $latestVersion->start_date->timezone('Asia/Kolkata')->format('Y-m-d H:i:s') : 'N/A';
        $endDate = $latestVersion ? $latestVersion->end_date->timezone('Asia/Kolkata')->format('Y-m-d H:i:s') : 'N/A';
        $description = $latestVersion ? $latestVersion->description : 'N/A';
        
        $reminders = $contract->reminders->pluck('days_before_end')->implode(', ');
        $recipients = $contract->notificationRecipients->pluck('name')->implode(', ');

        return [
            $contract->contract_number,
            $contract->branch->name ?? 'N/A',
            $contract->contractType->name ?? 'N/A',
            $contract->contract_with,
            $contract->grace_period_days,
            $contract->status,
            $contract->is_active ? 'Yes' : 'No',
            $latestVersion ? $latestVersion->version_number : 'N/A',
            $startDate,
            $endDate,
            $description,
            $contract->versions->count(),
            $reminders ?: 'None',
            $recipients ?: 'None',
            $contract->creator->name ?? 'N/A',
            $contract->created_at->timezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
            $contract->updater->name ?? 'N/A',
            $contract->updated_at->timezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
        ];
    }

    public function title(): string
    {
        return 'Contract Details';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

