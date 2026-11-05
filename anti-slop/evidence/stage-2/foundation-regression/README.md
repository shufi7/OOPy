# Pemetaan screenshot regresi fondasi

Cleanup 5 November 2026 mempertahankan satu salinan untuk 38 screenshot identik, total 5.345.861 byte. Tabel ini mencatat nama lama pada direktori ini, ukuran, SHA-256, dan lokasi kanonis di Tahap 1. Setiap pasangan diverifikasi ulang sebelum salinan redundan dihapus.

Metadata [foundation-results.json](foundation-results.json) tetap berada di direktori ini. Capture unik dan bukti skenario lain tetap dipertahankan. Pemetaan ini menyatakan kesamaan byte gambar; konteks regresi Tahap 2 tetap dicatat oleh metadata dan [laporan Tahap 2](../../../stage-2-2026-10-16.md).

`tests/browser/foundation.mjs` membaca JSON hasil/aset dan menulis screenshot ketika suite dijalankan. Suite tersebut tidak membaca PNG yang dideduplikasi. Menjalankan ulang suite dengan output direktori ini dapat menghasilkan kembali file dengan nama lama; gunakan direktori QA terpisah untuk pemeriksaan baru.

| Nama lama / salinan kanonis | Byte | SHA-256 |
| --- | ---: | --- |
| [coding-initial-1440.png](../../stage-1/coding-initial-1440.png) | 95324 | `caa2e972175e0d352ce2cb0b51a647020d1d122ac535c269f899f1d6f99338a4` |
| [coding-initial-390.png](../../stage-1/coding-initial-390.png) | 64879 | `c3730e132ea8cb751dba4d9bad0163c0d1cef6c3deafed020f56bcd69fb24d48` |
| [coding-latihan-1-initial-1440.png](../../stage-1/coding-latihan-1-initial-1440.png) | 133732 | `87ffeaff25ca11a52ae0ab59150cea283b4d2a76de17e5bddf670b72d65caabc` |
| [coding-latihan-1-initial-390.png](../../stage-1/coding-latihan-1-initial-390.png) | 84138 | `ee8a97db1320888a94d93f3ca0d79ba1af107016e76908caa100859e89334e1e` |
| [coding-latihan-2-initial-1440.png](../../stage-1/coding-latihan-2-initial-1440.png) | 131910 | `3f6f85ae39456c2ab3713f31437aba93ffb348e0e49b463abfaf1216c16d8cd1` |
| [coding-latihan-2-initial-390.png](../../stage-1/coding-latihan-2-initial-390.png) | 86094 | `824c423a2e5b165cf37d44e114138f02fca9a3dd3ce2adee00412e8a573db2e4` |
| [editor-after-1440.png](../../stage-1/editor-after-1440.png) | 63246 | `ee4c2312daf2c5f7a9b8a961e5feebb8ea41c781b00d4195a13c44babb3efc30` |
| [editor-after-390.png](../../stage-1/editor-after-390.png) | 37638 | `1b351f51cdf0f099343c58a0f1518d0037b83c737c50e872001d8d2be19415d4` |
| [editor-initial-1440.png](../../stage-1/editor-initial-1440.png) | 129666 | `d000fc8e3359c91afa5cf718e962995aa4b9438c99d1dc4113c4c8d1a7f6e3b9` |
| [editor-initial-390.png](../../stage-1/editor-initial-390.png) | 74624 | `e921d251f0c477806fca0b39d13592e256e9865d569615da9dfbea831807c631` |
| [login-initial-1440.png](../../stage-1/login-initial-1440.png) | 61035 | `420ef15ec17416c7e3023cdaf3ee7154cfd6c09a53a33220df13b190936ff83f` |
| [login-initial-390.png](../../stage-1/login-initial-390.png) | 45100 | `ab9fc460cd09dee14470b4edc93476d4b06b2b0a402486d85a62e6527030f3d0` |
| [materi-dasar-pemrograman-oop-initial-1440.png](../../stage-1/materi-dasar-pemrograman-oop-initial-1440.png) | 249566 | `4180781b8586443fc1f04e79b628610a50372afdaf16cd5951a0f8c1b26f8a89` |
| [materi-dasar-pemrograman-oop-initial-390.png](../../stage-1/materi-dasar-pemrograman-oop-initial-390.png) | 127563 | `2e9220e6130358f78813c80e0744eda7649db3e058ba03880f6777d943af2759` |
| [materi-enkapsulasi-initial-1440.png](../../stage-1/materi-enkapsulasi-initial-1440.png) | 265598 | `4c710c12fa734dbcb4ff7e68665ad4c8f9e644c82b9fac74975bcb848197a19c` |
| [materi-enkapsulasi-initial-390.png](../../stage-1/materi-enkapsulasi-initial-390.png) | 146667 | `02ac047c57a04f2179fd27d8d7eedadf775b832fb4835294e11c36e0374e6ab8` |
| [materi-evaluasi-akhir-hasil-completed-1440.png](../../stage-1/materi-evaluasi-akhir-hasil-completed-1440.png) | 193598 | `d563ce8241e8f0c629cb104382e366e32604dffec97dc93ecedace0a7466ea35` |
| [materi-evaluasi-akhir-hasil-completed-390.png](../../stage-1/materi-evaluasi-akhir-hasil-completed-390.png) | 88609 | `28b0a5a0f2063bcdaa4b3338ac2164607853ceb40c5e61d5471d9f67172ca07c` |
| [materi-evaluasi-akhir-hasil-initial-1440.png](../../stage-1/materi-evaluasi-akhir-hasil-initial-1440.png) | 192743 | `9fc5cdb785de545ff9befd28c81f773ea470d7a32a2c6df5930032c600ca2d30` |
| [materi-evaluasi-akhir-hasil-initial-390.png](../../stage-1/materi-evaluasi-akhir-hasil-initial-390.png) | 102077 | `e37f3d9a31b760ef786473bba1ee33dd4dc7641de22d6131d71fed214fbc6ae6` |
| [materi-evaluasi-akhir-initial-1440.png](../../stage-1/materi-evaluasi-akhir-initial-1440.png) | 228593 | `aee8f7728a6ed7690de7f10c638c67af9611631a1221fbc30d5826a4b4c63a2c` |
| [materi-evaluasi-akhir-initial-390.png](../../stage-1/materi-evaluasi-akhir-initial-390.png) | 109120 | `a9a6acbd2682ea269bc2e3aa7f88240cc2f00f77d49a4a4be34f8964923681e8` |
| [materi-evaluasi-akhir-ujian-active-1440.png](../../stage-1/materi-evaluasi-akhir-ujian-active-1440.png) | 210556 | `13859ea1718e8a39c109afedfff4d4f43f494a710a657160c3402cb2fac428e7` |
| [materi-evaluasi-akhir-ujian-active-390.png](../../stage-1/materi-evaluasi-akhir-ujian-active-390.png) | 89910 | `dbeeafe3f23149a609697c4fcbe252a2ee0079e0e316039aaa145a0cc7f8ec06` |
| [materi-evaluasi-akhir-ujian-initial-1440.png](../../stage-1/materi-evaluasi-akhir-ujian-initial-1440.png) | 183240 | `51ae5761ce7a9e12746fdd9ab691904c010326802b480b7497de4dda77f81d48` |
| [materi-evaluasi-akhir-ujian-initial-390.png](../../stage-1/materi-evaluasi-akhir-ujian-initial-390.png) | 97928 | `f484b3d0cd08577c05415f240d84e50e113d61ea9cce24e41a1f9e4141142e84` |
| [materi-initial-1440.png](../../stage-1/materi-initial-1440.png) | 256341 | `b10925843ca55bb469113e6835846773b143f47a7368a752a5d82bd93eb757e4` |
| [materi-initial-390.png](../../stage-1/materi-initial-390.png) | 87619 | `2c7732c3ec25d82b81809b18bafc6c290ae7bc5de7b16959e17ae07d927cc193` |
| [materi-kelas-abstrak-initial-1440.png](../../stage-1/materi-kelas-abstrak-initial-1440.png) | 246720 | `5c4293830089f469a796b83c55c29a38bd2d54ebdbe2a3cd39af21c6af3b2f37` |
| [materi-kelas-abstrak-initial-390.png](../../stage-1/materi-kelas-abstrak-initial-390.png) | 129708 | `ef03ba532330f7166a9d737f2b195c6f4947042637002fa108c57b3dbd810447` |
| [materi-kelas-dan-objek-initial-1440.png](../../stage-1/materi-kelas-dan-objek-initial-1440.png) | 250853 | `0d1b20ce1d4fe7adf3f8a8375b5e2791ba8ad5b629c612c561ef4d26ca89a545` |
| [materi-kelas-dan-objek-initial-390.png](../../stage-1/materi-kelas-dan-objek-initial-390.png) | 142601 | `d976cb590277a761f8d57ab7c0a2cddc61946bd8ddfaea5ebd6b1514acea8d48` |
| [materi-pewarisan-initial-1440.png](../../stage-1/materi-pewarisan-initial-1440.png) | 256557 | `b724f39e15dd4483aa7583b28c8540841bdf4add2d727f16f768c0797fdd0f69` |
| [materi-pewarisan-initial-390.png](../../stage-1/materi-pewarisan-initial-390.png) | 144031 | `0794e8b666f3e25fbcc39317bccc921587f6eb3a06e633b24c33348c21e6d55d` |
| [materi-polimorfisme-initial-1440.png](../../stage-1/materi-polimorfisme-initial-1440.png) | 274234 | `773f7df9d4e4ede98a90ca7497d18f700a1f652bb5ae7beac975a48531c7729b` |
| [materi-polimorfisme-initial-390.png](../../stage-1/materi-polimorfisme-initial-390.png) | 145286 | `33e1c8b7e2de8b0f5015cab8df3754f417f111aa9a69af4ab64768b6835a4419` |
| [register-initial-1440.png](../../stage-1/register-initial-1440.png) | 64663 | `856347da27505efdbf1f9426c0b7e274a3ce32a8575d5928d64ab7cb254cf087` |
| [register-initial-390.png](../../stage-1/register-initial-390.png) | 54094 | `f969b2c6fde0d03b137b534f0855201983ca4c0a3484c329eee65bbf47ba2101` |
