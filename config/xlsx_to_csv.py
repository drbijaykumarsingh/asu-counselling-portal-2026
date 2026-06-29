#!/usr/bin/env python3
"""
config/xlsx_to_csv.py
Converts an XLSX/XLS file to CSV.
Called by process_upload.php when PhpSpreadsheet is not installed.
Usage: python3 xlsx_to_csv.py input.xlsx output.csv
"""
import sys
import csv

def convert(src, dst):
    ext = src.rsplit('.', 1)[-1].lower()
    if ext in ('xlsx', 'xlsm'):
        import openpyxl
        wb = openpyxl.load_workbook(src, read_only=True, data_only=True)
        ws = wb.active
        rows = ws.iter_rows(values_only=True)
    elif ext == 'xls':
        import xlrd
        wb = xlrd.open_workbook(src)
        ws = wb.sheet_by_index(0)
        rows = (ws.row_values(r) for r in range(ws.nrows))
    else:
        print(f"Unsupported extension: {ext}", file=sys.stderr)
        sys.exit(1)

    with open(dst, 'w', newline='', encoding='utf-8') as f:
        writer = csv.writer(f)
        for row in rows:
            writer.writerow(['' if v is None else str(v) for v in row])

    print(f"Converted {src} → {dst}")

if __name__ == '__main__':
    if len(sys.argv) < 3:
        print("Usage: xlsx_to_csv.py input.xlsx output.csv", file=sys.stderr)
        sys.exit(1)
    convert(sys.argv[1], sys.argv[2])
