<?php
/**
 * Export System
 *
 * Export analysis results to various formats: PDF, CSV, JSON, Excel
 *
 * @package ControlloDomin
 * @version 4.2.0
 */

require_once __DIR__ . '/database.php';

class ExportManager {
    private $db;
    private $export_dir;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->export_dir = __DIR__ . '/../exports';

        // Create export directory if not exists
        if (!is_dir($this->export_dir)) {
            mkdir($this->export_dir, 0755, true);
        }
    }

    /**
     * Export analysis results
     *
     * @param string $user_id User UUID
     * @param string $format Export format (pdf, csv, json, excel)
     * @param array $analysis_ids Analysis IDs to export
     * @param string $domain Specific domain (optional)
     * @return array Export result
     */
    public function export($user_id, $format, $analysis_ids = [], $domain = null) {
        try {
            // Validate format
            if (!in_array($format, ['pdf', 'csv', 'json', 'excel'])) {
                throw new Exception('Invalid export format');
            }

            // Get analysis data
            $data = $this->getAnalysisData($user_id, $analysis_ids, $domain);

            if (empty($data)) {
                throw new Exception('No data to export');
            }

            // Generate export file
            $file_path = $this->generateExport($format, $data, $user_id);

            // Create export record
            $export_id = $this->db->insert('exports', [
                'user_id' => $user_id,
                'export_type' => $format,
                'domain' => $domain,
                'analysis_ids' => '{' . implode(',', $analysis_ids) . '}',
                'file_path' => $file_path,
                'file_size' => filesize($file_path),
                'status' => 'completed',
                'expires_at' => date('Y-m-d H:i:s', time() + 86400), // 24 hours
                'completed_at' => date('Y-m-d H:i:s')
            ]);

            return [
                'success' => true,
                'export_id' => $export_id,
                'file_path' => $file_path,
                'download_url' => '/download/' . basename($file_path),
                'expires_at' => date('Y-m-d H:i:s', time() + 86400)
            ];

        } catch (Exception $e) {
            // Log error
            if (isset($export_id)) {
                $this->db->update(
                    'exports',
                    [
                        'status' => 'failed',
                        'error_message' => $e->getMessage()
                    ],
                    'id = :id',
                    [':id' => $export_id]
                );
            }

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get analysis data for export
     */
    private function getAnalysisData($user_id, $analysis_ids, $domain) {
        $query = 'SELECT * FROM analysis_history WHERE user_id = :user_id';
        $params = [':user_id' => $user_id];

        if (!empty($analysis_ids)) {
            $placeholders = implode(',', array_fill(0, count($analysis_ids), '?'));
            $query .= " AND id IN ($placeholders)";
            foreach ($analysis_ids as $id) {
                $params[] = $id;
            }
        }

        if ($domain) {
            $query .= ' AND domain = :domain';
            $params[':domain'] = $domain;
        }

        $query .= ' ORDER BY created_at DESC LIMIT 1000';

        return $this->db->query($query, $params);
    }

    /**
     * Generate export file
     */
    private function generateExport($format, $data, $user_id) {
        $filename = sprintf(
            'export_%s_%s.%s',
            $user_id,
            date('Y-m-d_His'),
            $format === 'excel' ? 'xlsx' : $format
        );

        $file_path = $this->export_dir . '/' . $filename;

        switch ($format) {
            case 'json':
                return $this->exportJSON($data, $file_path);
            case 'csv':
                return $this->exportCSV($data, $file_path);
            case 'pdf':
                return $this->exportPDF($data, $file_path);
            case 'excel':
                return $this->exportExcel($data, $file_path);
            default:
                throw new Exception('Unsupported format');
        }
    }

    /**
     * Export to JSON
     */
    private function exportJSON($data, $file_path) {
        $json = json_encode($data, JSON_PRETTY_PRINT);
        file_put_contents($file_path, $json);
        return $file_path;
    }

    /**
     * Export to CSV
     */
    private function exportCSV($data, $file_path) {
        $fp = fopen($file_path, 'w');

        // Write header
        $headers = ['ID', 'Domain', 'Analysis Type', 'Created At', 'Execution Time (ms)', 'From Cache'];
        fputcsv($fp, $headers);

        // Write data rows
        foreach ($data as $row) {
            $csv_row = [
                $row['id'],
                $row['domain'],
                $row['analysis_type'],
                $row['created_at'],
                $row['execution_time_ms'] ?? 0,
                $row['from_cache'] ? 'Yes' : 'No'
            ];
            fputcsv($fp, $csv_row);
        }

        fclose($fp);
        return $file_path;
    }

    /**
     * Export to PDF
     */
    private function exportPDF($data, $file_path) {
        // Basic HTML to PDF conversion
        // In production, use a library like TCPDF or mPDF

        $html = $this->generatePDFHTML($data);

        // For now, save as HTML (in production, convert to actual PDF)
        $html_file = str_replace('.pdf', '.html', $file_path);
        file_put_contents($html_file, $html);

        // TODO: Convert HTML to PDF using TCPDF, Dompdf, or wkhtmltopdf
        // For now, return HTML file
        return $html_file;
    }

    /**
     * Generate HTML for PDF
     */
    private function generatePDFHTML($data) {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Domain Analysis Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 40px;
            line-height: 1.6;
        }
        h1 {
            color: #007bff;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #007bff;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <h1>Domain Analysis Report</h1>
    <p>Generated on: ' . date('F d, Y H:i:s') . '</p>
    <p>Total analyses: ' . count($data) . '</p>

    <table>
        <thead>
            <tr>
                <th>Domain</th>
                <th>Analysis Type</th>
                <th>Date</th>
                <th>From Cache</th>
            </tr>
        </thead>
        <tbody>';

        foreach ($data as $row) {
            $html .= '<tr>
                <td>' . htmlspecialchars($row['domain']) . '</td>
                <td>' . htmlspecialchars($row['analysis_type']) . '</td>
                <td>' . htmlspecialchars($row['created_at']) . '</td>
                <td>' . ($row['from_cache'] ? 'Yes' : 'No') . '</td>
            </tr>';
        }

        $html .= '</tbody>
    </table>

    <div class="footer">
        <p>Generated by Controllo Domini - https://controllodomini.it</p>
        <p>This report is confidential and intended only for the recipient.</p>
    </div>
</body>
</html>';

        return $html;
    }

    /**
     * Export to Excel
     */
    private function exportExcel($data, $file_path) {
        // Basic CSV export (can be opened in Excel)
        // In production, use PhpSpreadsheet for actual .xlsx files
        return $this->exportCSV($data, $file_path);
    }

    /**
     * Get user's exports
     */
    public function getUserExports($user_id, $limit = 50) {
        return $this->db->query(
            'SELECT id, export_type, domain, file_size, status,
                    created_at, completed_at, expires_at
             FROM exports
             WHERE user_id = :user_id
             AND (expires_at IS NULL OR expires_at > NOW())
             ORDER BY created_at DESC
             LIMIT :limit',
            [':user_id' => $user_id, ':limit' => $limit]
        );
    }

    /**
     * Delete expired exports
     */
    public function cleanupExpiredExports() {
        // Get expired exports
        $expired = $this->db->query(
            'SELECT id, file_path FROM exports
             WHERE expires_at < NOW() AND status = :status',
            [':status' => 'completed']
        );

        $deleted = 0;
        foreach ($expired as $export) {
            // Delete file
            if (file_exists($export['file_path'])) {
                unlink($export['file_path']);
            }

            // Delete record
            $this->db->delete('exports', 'id = :id', [':id' => $export['id']]);
            $deleted++;
        }

        return [
            'success' => true,
            'deleted' => $deleted,
            'message' => "Cleaned up $deleted expired exports"
        ];
    }

    /**
     * Export quick analysis (without saving to database)
     */
    public function quickExport($format, $data, $filename = null) {
        if (!$filename) {
            $filename = 'analysis_' . date('Y-m-d_His') . '.' . $format;
        }

        $file_path = $this->export_dir . '/' . $filename;

        switch ($format) {
            case 'json':
                file_put_contents($file_path, json_encode($data, JSON_PRETTY_PRINT));
                break;
            case 'csv':
                // Convert data to CSV format
                $fp = fopen($file_path, 'w');
                if (!empty($data)) {
                    fputcsv($fp, array_keys($data[0]));
                    foreach ($data as $row) {
                        fputcsv($fp, $row);
                    }
                }
                fclose($fp);
                break;
            default:
                throw new Exception('Quick export only supports JSON and CSV');
        }

        return $file_path;
    }
}

/**
 * Helper function to get ExportManager instance
 */
function getExportManager() {
    static $manager = null;
    if ($manager === null) {
        $manager = new ExportManager();
    }
    return $manager;
}

/**
 * Helper function for quick JSON export
 */
function exportToJSON($data, $filename = null) {
    $manager = getExportManager();
    return $manager->quickExport('json', $data, $filename);
}

/**
 * Helper function for quick CSV export
 */
function exportToCSV($data, $filename = null) {
    $manager = getExportManager();
    return $manager->quickExport('csv', $data, $filename);
}
