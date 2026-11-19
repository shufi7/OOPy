"""Validate and render PDFs from the disposable certification browser fixture."""
import json
import re
import sys
from pathlib import Path

import pymupdf as fitz

directory = Path(sys.argv[1]).resolve()
assert re.fullmatch(r"certification-[a-f0-9]{32}", directory.name)
assert directory.parent.name == "testing"
results = []
for filename in ["certificate.pdf", "certificate-redownload.pdf", "certificate-http.pdf", "certificate-long-name.pdf"]:
    with fitz.open(directory / filename) as document:
        assert len(document) == 1, f"{filename}: exactly one page"
        page = document[0]
        assert abs(page.rect.width - 841.89) < 0.1
        assert abs(page.rect.height - 595.28) < 0.1
        text = page.get_text()
        for required in ["SIMULASI / DEMO", "CERTIFICATE OF COMPLETION", "OOPy Learning Platform", "OOPY-SIM-", "Selesai:", "Diterbitkan:", "Pemrograman Berorientasi Objek dengan Python"]:
            assert required in text, f"{filename}: missing {required}"
        assert ("Akhir" if "long-name" in filename else "Peserta Sertifikasi Eligible") in text
        assert len(page.get_images()) >= 2, "Logo and QR embedded in PDF"
        for x0, y0, x1, y1, *_ in page.get_text("words"):
            assert 0 <= x0 < x1 <= page.rect.width and 0 <= y0 < y1 <= page.rect.height, "No text cut by page boundaries"
        page.get_pixmap(matrix=fitz.Matrix(1.6, 1.6)).save(directory / f"{filename}.png")
        (directory / f"{filename}.txt").write_text(text, encoding="utf-8")
        results.append({"file": filename, "pages": 1, "width": page.rect.width, "height": page.rect.height, "images": len(page.get_images())})
(directory / "pdf-results.json").write_text(json.dumps({"files": results}, indent=2), encoding="utf-8")
