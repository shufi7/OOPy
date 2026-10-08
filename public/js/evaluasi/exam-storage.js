import { decodeState, emptyState, mergeStates } from './exam-model.js';

export class ExamStorage {
    constructor(driver, questions, settings, notify = () => {}) {
        this.driver = driver;
        this.questions = questions;
        this.settings = settings;
        this.notify = notify;
        this.state = emptyState();
        this.state = this.read();
    }

    read() {
        try {
            const decoded = decodeState(this.driver.getItem(this.settings.storage_key), this.questions, this.settings);
            if (decoded.invalid) this.notify('Data evaluasi yang rusak atau tidak sesuai versi diabaikan. Riwayat valid tetap dipertahankan; periksa sesi sebelum melanjutkan.');
            return decoded.state;
        } catch {
            this.notify('Penyimpanan browser tidak dapat dibaca. Izinkan localStorage dan muat ulang sebelum memulai evaluasi.');
            return this.state;
        }
    }

    save(incoming) {
        this.state = mergeStates(mergeStates(this.read(), this.state), incoming);
        try {
            this.driver.setItem(this.settings.storage_key, JSON.stringify(this.state));
            return true;
        } catch {
            this.notify('Jawaban atau hasil terbaru belum dapat disimpan. Pertahankan halaman ini tetap terbuka; perubahan dapat hilang saat refresh atau berpindah halaman.');
            return false;
        }
    }
}
