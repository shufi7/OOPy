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
verification_url = (directory / "verification-url.txt").read_text(encoding="utf-8")
detector = cv2.QRCodeDetector()
for filename in ["certificate-http.pdf", "certificate-sandbox.pdf", "certificate-long-name.pdf",
                 "certificate-unbroken-name.pdf", "certificate-corrected.pdf", "certificate-legacy.pdf"]:
    legacy = filename == "certificate-legacy.pdf"
    with fitz.open(directory / filename) as document:
        assert len(document) == (1 if legacy else 2), f"{filename}: incorrect page count {len(document)}"
        cover = document[0]
        number = re.search(r"OOPY-(?:SBX|SIM)-[A-F0-9]+", cover.get_text()).group()
        for index, page in enumerate(document, start=1):
            assert abs(page.rect.width - 841.89) < 0.1
            assert abs(page.rect.height - 595.28) < 0.1
            text = page.get_text()
            for required in [number, "Pemrograman Berorientasi Objek dengan Python",
                             "Sertifikat demo penyelesaian pembelajaran OOPy" if index == 1 else "Transkrip demo OOPy"]:
                assert required in text, f"{filename}, page {index}: missing {required}"
            for removed in ["SANDBOX / DEMO", "SIMULASI / DEMO", "Skala 0", "Kuis: nilai resmi terbaik",
                            "Coding: nilai terbaik dari grader", "Evaluasi Akhir: nilai terbaik dari percobaan lulus"]:
                assert removed not in text, f"{filename}, page {index}: found removed text {removed}"
            if filename == "certificate-unbroken-name.pdf":
                assert sum(map(len, re.findall(r"\bW+\b", text))) == 100, (index, text)
            else:
                name = ("Nama Peserta Panjang " * 4 + "Àkhir" if "long-name" in filename
                        else "Rara Áyu O’Malley" if filename == "certificate-corrected.pdf"
                        else "Peserta Sandbox Legacy" if legacy else "Rara Dwi O’Malley-Santoso")
                assert name in " ".join(text.split()), (index, text)
            if not legacy:
                assert f"{index}/2" in text
            if filename == "certificate-corrected.pdf":
                assert "Koreksi nama" in text and "Rara Dwi O’Malley-Santoso" not in text
            if index == 1:
                assert "CERTIFICATE OF COMPLETION" in text
            else:
                for required in ["TRANSKRIP NILAI", "Learning Achievement Transcript", "Komponen Penilaian",
                                 *[f"BAB {chapter}" for chapter in range(1, 7)], "Latihan 1", "Latihan 2",
                                 "Evaluasi Akhir", "91,25", "92,5", "84,75", "Status kelulusan",
                                 "oopy-transkrip-v1"]:
                    assert required in text, f"{filename}: transcript missing {required}"
            for x0, y0, x1, y1, word, *_ in page.get_text("words"):
                assert 26 <= x0 < x1 <= page.rect.width - 26 and 26 <= y0 < y1 <= page.rect.height - 20, (
                    filename, index, word, (x0, y0, x1, y1))
            page.get_pixmap(matrix=fitz.Matrix(1.6, 1.6)).save(directory / f"{filename}-p{index}.png")
        assert len(cover.get_images()) >= 2, f"{filename}: logo and QR are embedded"
        embedded_qr = False
        for image in cover.get_images():
            extracted = document.extract_image(image[0])
            pixmap = fitz.Pixmap(extracted["image"])
            qr_file = directory / f"{filename}-image-{image[0]}.png"
            pixmap.save(qr_file)
            decoded, *_ = detector.detectAndDecode(cv2.imread(str(qr_file)))
            if decoded:
                assert re.fullmatch(r"http://127\.0\.0\.1:\d+/sertifikat/verifikasi/[0-9a-f-]+", decoded)
                assert legacy or decoded == verification_url
                embedded_qr = True
        assert embedded_qr, f"{filename}: embedded QR decodes"
        results.append({"file": filename, "pages": len(document), "embedded_qr": True})
decoded, *_ = detector.detectAndDecode(cv2.imread(str(directory / "qr.png")))
assert decoded == verification_url
(directory / "pdf-results.json").write_text(json.dumps({"files": results, "qr_matches_verification": True}, indent=2), encoding="utf-8")
print("PASS: five two-page PDFs and one legacy PDF, A4 landscape, corrected and original names, scores, and embedded verification QR")
