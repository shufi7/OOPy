"""Inspect PDFs and decode the QR from the isolated Midtrans browser run."""
import json
import re
import sys
from pathlib import Path

import cv2
import pymupdf as fitz

directory = Path(sys.argv[1]).resolve()
assert re.fullmatch(r"midtrans-[a-f0-9]{32}", directory.name)
assert directory.parent.name == "testing"
results = []
for filename in ["certificate-http.pdf", "certificate-sandbox.pdf", "certificate-long-name.pdf"]:
    with fitz.open(directory / filename) as document:
        assert len(document) == 1, f"{filename}: exactly one page"
        page = document[0]
        assert abs(page.rect.width - 841.89) < 0.1
        assert abs(page.rect.height - 595.28) < 0.1
        text = page.get_text()
        for required in ["CERTIFICATE OF COMPLETION", "OOPY-SBX-", "Pemrograman Berorientasi Objek dengan Python"]:
            assert required in text, f"{filename}: missing {required}"
        assert "SANDBOX / DEMO" not in text
        assert "Sertifikat demo penyelesaian pembelajaran OOPy" not in text
        assert ("Akhir" if "long-name" in filename else "Peserta Sandbox Eligible") in text
        assert len(page.get_images()) >= 2
        for x0, y0, x1, y1, *_ in page.get_text("words"):
            assert 0 <= x0 < x1 <= page.rect.width and 0 <= y0 < y1 <= page.rect.height, "No clipped text"
        page.get_pixmap(matrix=fitz.Matrix(1.6, 1.6)).save(directory / f"{filename}.png")
        results.append({"file": filename, "pages": 1, "images": len(page.get_images())})
decoded, *_ = cv2.QRCodeDetector().detectAndDecode(cv2.imread(str(directory / "qr.png")))
assert decoded == (directory / "verification-url.txt").read_text(encoding="utf-8")
(directory / "pdf-results.json").write_text(json.dumps({"files": results, "qr_matches_verification": True}, indent=2), encoding="utf-8")
print("PASS: three A4 landscape PDFs, no demo text, long name, embedded images, no clipped text, QR verification URL")
