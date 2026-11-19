// Short, ungraded checks are attached by existing section IDs so published
// material and optional section fields keep the same data contract.
const learningChecks = {
    'nilai-tipe-data-variabel': {
        kind: 'Kenali tipe data',
        prompt: 'Variabel ph pada potongan kode ini menyimpan nilai bertipe apa?',
        code: 'ph = 7.2\nprint(type(ph))',
        options: [
            { label: 'int', correct: false, feedback: 'int digunakan untuk bilangan bulat tanpa bagian desimal.' },
            { label: 'float', correct: true, feedback: 'Angka 7.2 memiliki bagian desimal sehingga bertipe float.' },
            { label: 'str', correct: false, feedback: 'str digunakan untuk teks yang ditulis di antara tanda kutip.' },
        ],
    },
    percabangan: {
        kind: 'Prediksi output',
        prompt: 'Jika tinggi_air bernilai 135, teks apa yang ditampilkan program berikut?',
        code: 'tinggi_air = 135\n\nif tinggi_air >= 150:\n    status = "Waspada"\nelif tinggi_air >= 100:\n    status = "Perlu dipantau"\nelse:\n    status = "Normal"\n\nprint(status)',
        options: [
            { label: 'Waspada', correct: false, feedback: 'Kondisi pertama hanya benar jika tinggi_air minimal 150.' },
            { label: 'Perlu dipantau', correct: true, feedback: '135 tidak memenuhi kondisi pertama, tetapi memenuhi kondisi elif karena nilainya minimal 100.' },
            { label: 'Normal', correct: false, feedback: 'else berjalan hanya jika kondisi if dan elif sama-sama salah.' },
        ],
    },
    'perulangan-list': {
        kind: 'Prediksi output',
        prompt: 'Berapa kali teks "Memeriksa:" muncul ketika loop ini dijalankan?',
        code: 'lokasi = ["Sungai", "Rawa", "Mangrove"]\n\nfor nama in lokasi:\n    print("Memeriksa:", nama)',
        options: [
            { label: 'Satu kali', correct: false, feedback: 'Loop for berjalan untuk setiap item di dalam list.' },
            { label: 'Tiga kali', correct: true, feedback: 'List memiliki tiga item, jadi blok di dalam for dijalankan tiga kali.' },
            { label: 'Empat kali', correct: false, feedback: 'Loop tidak menambahkan putaran setelah semua item list selesai diproses.' },
        ],
    },
    'prosedural-ke-oop': {
        kind: 'Pilih pendekatan',
        prompt: 'Program memantau banyak sensor. Setiap sensor memiliki lokasi, nilai, dan perilaku baca(). Apa manfaat utama memodelkannya sebagai object?',
        visualization: {
            caption: 'Dari fungsi terpisah menuju object yang menyatukan data dan perilaku',
            layout: 'compare',
            items: [
                { label: 'Prosedural', code: 'data sensor + baca_sensor(data)', detail: 'Data dan fungsi dikelola terpisah.' },
                { label: 'Berorientasi object', code: 'Sensor(lokasi, nilai) · sensor.baca()', detail: 'Setiap object membawa state dan perilaku yang berkaitan.' },
            ],
        },
        options: [
            { label: 'Data dan perilaku yang berkaitan dapat dikelompokkan per object', correct: true, feedback: 'Class dapat menjadi rancangan bersama, sementara tiap object menyimpan state dan menjalankan perilakunya.' },
            { label: 'Semua fungsi harus dihapus dari program', correct: false, feedback: 'OOP tetap dapat menggunakan fungsi; method adalah perilaku yang terkait dengan object.' },
            { label: 'Semua sensor harus memakai nilai yang sama', correct: false, feedback: 'Object dari class yang sama dapat memiliki data instance yang berbeda.' },
        ],
    },
    'class-object': {
        kind: 'Pahami konsep',
        prompt: 'Pada baris rawa = Ekosistem("Rawa", "Kalimantan Selatan"), apa yang dimaksud dengan rawa?',
        visualization: {
            caption: 'Satu class dapat digunakan untuk membuat beberapa object',
            layout: 'flow',
            items: [
                { label: 'Class', code: 'Ekosistem', detail: 'Cetak biru berisi aturan object.' },
                { label: 'Object pertama', code: 'rawa', detail: 'nama = Rawa · lokasi = Kalimantan Selatan' },
                { label: 'Object lain', code: 'sungai', detail: 'nama = Sungai · lokasi = Banjar' },
            ],
        },
        options: [
            { label: 'Class yang menjadi cetak biru', correct: false, feedback: 'Ekosistem adalah class yang mendeskripsikan object sejenis.' },
            { label: 'Object yang dibuat dari class Ekosistem', correct: true, feedback: 'Pemanggilan Ekosistem(...) membuat satu object; variabel rawa merujuk pada object tersebut.' },
            { label: 'Method yang membuat class', correct: false, feedback: 'Ekosistem(...) memanggil constructor untuk membuat object, bukan method info().' },
        ],
    },
    'banyak-object': {
        kind: 'Pahami constructor',
        prompt: 'Kapan method __init__ dijalankan dan apa tugas utamanya?',
        visualization: {
            caption: 'Constructor menerima nilai awal untuk tiap object yang dibuat',
            layout: 'flow',
            items: [
                { label: 'Rancangan', code: 'class Ekosistem', detail: 'Mendefinisikan atribut dan method bersama.' },
                { label: 'Pemanggilan pertama', code: 'Ekosistem("Rawa", ...)', detail: '__init__ menyimpan data pada object pertama.' },
                { label: 'Pemanggilan berikutnya', code: 'Ekosistem("Sungai", ...)', detail: '__init__ memberi nilai awal yang berbeda.' },
            ],
        },
        options: [
            { label: 'Saat object dibuat, untuk memberi nilai awal pada object', correct: true, feedback: '__init__ menerima object melalui self dan nilai awal dari argument constructor.' },
            { label: 'Setiap kali program memanggil print()', correct: false, feedback: '__init__ berhubungan dengan inisialisasi object, bukan pencetakan output.' },
            { label: 'Sebelum class didefinisikan, untuk membuat cetak biru', correct: false, feedback: 'Class didefinisikan lebih dulu; __init__ dijalankan saat object dibuat.' },
        ],
    },
    'live-coding-spesies': {
        kind: 'Pahami self',
        prompt: 'Saat sebuah object memanggil method info(), apa yang dirujuk oleh parameter self?',
        options: [
            { label: 'Class sebagai cetak biru', correct: false, feedback: 'Method instance menerima object yang memanggilnya.' },
            { label: 'Object yang sedang memanggil method', correct: true, feedback: 'Python meneruskan object sebagai self sehingga method dapat membaca atribut milik object itu.' },
            { label: 'Nilai teks salah satu atribut saja', correct: false, feedback: 'Atribut dapat dibaca melalui self; self sendiri merujuk pada object.' },
        ],
    },
    'atribut-method': {
        kind: 'Pahami konsep',
        prompt: 'Dalam class SensorAir, manakah pasangan atribut dan method yang tepat?',
        options: [
            { label: 'lokasi adalah atribut; baca() adalah method', correct: true, feedback: 'lokasi menyimpan data object, sedangkan baca() adalah perilaku yang dapat dipanggil.' },
            { label: 'lokasi adalah method; baca() adalah atribut', correct: false, feedback: 'Nama dengan tanda kurung seperti baca() menunjukkan pemanggilan perilaku.' },
            { label: 'Keduanya selalu merupakan atribut', correct: false, feedback: 'Method adalah fungsi yang didefinisikan pada class dan dipanggil melalui object.' },
        ],
    },
    'non-public': {
        kind: 'Pahami konsep',
        prompt: 'Apa arti satu underscore pada atribut self._lokasi di Python?',
        options: [
            { label: 'Python melarang akses dari luar class', correct: false, feedback: 'Satu underscore tidak membatasi akses secara teknis.' },
            { label: 'Atribut dianggap untuk penggunaan internal berdasarkan konvensi', correct: true, feedback: 'Nama itu tetap dapat diakses, tetapi underscore memberi isyarat agar pengguna memakai interface class.' },
            { label: 'Nilai atribut otomatis dienkripsi', correct: false, feedback: 'Underscore bukan mekanisme keamanan atau enkripsi.' },
        ],
    },
    'name-mangling': {
        kind: 'Pahami name mangling',
        prompt: 'Apa arti atribut self.__tinggi_air di dalam class SensorAir?',
        options: [
            { label: 'Atribut terenkripsi dan tidak dapat ditemukan', correct: false, feedback: 'Name mangling bukan enkripsi dan bukan perlindungan keamanan absolut.' },
            { label: 'Nama atribut diubah untuk membantu menghindari konflik nama', correct: true, feedback: 'Python mengubahnya menjadi nama seperti _SensorAir__tinggi_air; nama hasilnya masih dapat diakses.' },
            { label: 'Atribut otomatis menjadi property', correct: false, feedback: 'Property dibuat dengan decorator @property, bukan dengan dua underscore.' },
        ],
    },
    'getter-setter': {
        kind: 'Temukan masalah kode',
        prompt: 'Setter berikut gagal karena memanggil dirinya berulang kali. Baris mana yang sebaiknya menyimpan nilai internal?',
        code: '@property\ndef tinggi_air(self):\n    return self.__tinggi_air\n\n@tinggi_air.setter\ndef tinggi_air(self, nilai):\n    if nilai < 0:\n        raise ValueError("Nilai tidak valid")\n    self.tinggi_air = nilai',
        options: [
            { label: 'self.tinggi_air = nilai', correct: false, feedback: 'Assignment ke property yang sama akan memanggil setter ini lagi.' },
            { label: 'self.__tinggi_air = nilai', correct: true, feedback: 'Simpan ke atribut internal agar setter tidak memanggil dirinya sendiri.' },
            { label: 'return nilai', correct: false, feedback: 'return tidak menyimpan nilai baru pada object.' },
        ],
    },
    property: {
        kind: 'Pilih hasil validasi',
        prompt: 'Setter menerima nilai tinggi air yang tidak negatif. Apa hasil ketika sensor.tinggi_air diubah menjadi -10?',
        code: 'sensor.tinggi_air = -10',
        visualization: {
            caption: 'Property meneruskan assignment ke setter sebelum nilai disimpan',
            layout: 'compare',
            items: [
                { label: 'Nilai valid', code: '90', detail: 'Lolos pemeriksaan lalu disimpan ke self.__tinggi_air.' },
                { label: 'Nilai negatif', code: '-10', detail: 'Setter menimbulkan ValueError; nilai lama tidak berubah.' },
            ],
        },
        options: [
            { label: 'Nilai -10 tersimpan tanpa pemeriksaan', correct: false, feedback: 'Assignment memanggil setter sehingga pemeriksaan nilai tetap berjalan.' },
            { label: 'Setter menimbulkan ValueError sebelum menyimpan -10', correct: true, feedback: 'Kondisi nilai negatif memicu ValueError; atribut internal tetap menyimpan nilai valid sebelumnya.' },
            { label: 'Python otomatis mengubah -10 menjadi 0', correct: false, feedback: 'Setter contoh menolak nilai negatif, bukan mengubahnya menjadi nilai lain.' },
        ],
    },
    'super-duplikasi': {
        kind: 'Pahami konsep',
        prompt: 'Mengapa constructor Sungai memanggil super().__init__(nama, lokasi)?',
        visualization: {
            caption: 'super() memakai inisialisasi superclass pada object Sungai yang sama',
            layout: 'flow',
            items: [
                { label: 'Membuat object', code: 'Sungai("Martapura", ...)', detail: 'Python memanggil constructor Sungai.' },
                { label: 'Memakai constructor induk', code: 'super().__init__(nama, lokasi)', detail: 'Nama dan lokasi disimpan lewat inisialisasi Ekosistem.' },
                { label: 'Melanjutkan constructor', code: 'self.panjang_km = ...', detail: 'Sungai menambahkan atribut khususnya sendiri.' },
            ],
        },
        options: [
            { label: 'Untuk membuat object Ekosistem kedua', correct: false, feedback: 'super() tidak membuat object baru.' },
            { label: 'Untuk memakai inisialisasi nama dan lokasi dari superclass', correct: true, feedback: 'Pemanggilan ini menginisialisasi bagian Ekosistem pada object Sungai yang sama.' },
            { label: 'Untuk mengganti method info() secara otomatis', correct: false, feedback: 'Overriding dilakukan dengan mendefinisikan method bernama sama pada subclass.' },
        ],
    },
    'superclass-subclass': {
        kind: 'Kenali pewarisan',
        prompt: 'Pada class Sungai(Ekosistem), class mana yang menjadi superclass dan mana yang menjadi subclass?',
        visualization: {
            caption: 'Subclass mewarisi sifat umum lalu menambahkan ciri khusus',
            layout: 'compare',
            items: [
                { label: 'Superclass', code: 'Ekosistem', detail: 'Menyediakan nama, lokasi, dan perilaku umum.' },
                { label: 'Subclass', code: 'Sungai(Ekosistem)', detail: 'Mewarisi bagian umum dan dapat menambah panjang_km.' },
                { label: 'Subclass lain', code: 'Rawa(Ekosistem)', detail: 'Mewarisi bagian umum dan dapat menambah luas_ha.' },
            ],
        },
        options: [
            { label: 'Ekosistem superclass; Sungai subclass', correct: true, feedback: 'Class di dalam tanda kurung adalah class yang diwarisi; Sungai menjadi jenis khusus dari Ekosistem.' },
            { label: 'Sungai superclass; Ekosistem subclass', correct: false, feedback: 'Arah pewarisan dibaca dari deklarasi Sungai(Ekosistem).' },
            { label: 'Keduanya object, bukan class', correct: false, feedback: 'Keduanya adalah class; object dibuat kemudian dengan memanggil constructor class.' },
        ],
    },
    'inheritance-composition': {
        kind: 'Pilih hubungan yang tepat',
        prompt: 'StasiunPemantau menyimpan beberapa object SensorLingkungan. Hubungan kelas mana yang paling sesuai?',
        visualization: {
            caption: 'Pilih hubungan kelas berdasarkan makna relasinya',
            layout: 'compare',
            items: [
                { label: 'Inheritance · is-a', code: 'Sungai adalah Ekosistem', detail: 'Subclass mewarisi perilaku dari superclass.' },
                { label: 'Composition · has-a', code: 'Stasiun memiliki sensor', detail: 'Object SensorLingkungan disimpan sebagai bagian dari StasiunPemantau.' },
            ],
        },
        options: [
            { label: 'Inheritance, karena stasiun adalah jenis sensor', correct: false, feedback: 'Stasiun bukan jenis sensor; relasi ini tidak menyatakan hubungan is-a.' },
            { label: 'Composition, karena stasiun memiliki sensor', correct: true, feedback: 'Hubungan has-a dimodelkan dengan menyimpan object sensor di dalam StasiunPemantau.' },
            { label: 'Overriding, karena stasiun memanggil sensor', correct: false, feedback: 'Overriding berkaitan dengan implementasi ulang method pada subclass.' },
        ],
    },
    'polimorfisme-inheritance': {
        kind: 'Prediksi perilaku method',
        prompt: 'SensorPH dan SensorSuhu meng-override baca_data(). Apa yang dicetak loop tanpa memeriksa jenis object?',
        code: 'sensor = [SensorPH(), SensorSuhu()]\n\nfor item in sensor:\n    print(item.baca_data())',
        visualization: {
            caption: 'Nama method dan cara pemanggilannya tetap sama; implementasi mengikuti class object',
            layout: 'compare',
            items: [
                { label: 'Object pertama', code: 'SensorPH.baca_data()', detail: 'Implementasi override menghasilkan 7.1.' },
                { label: 'Object berikutnya', code: 'SensorSuhu.baca_data()', detail: 'Implementasi override menghasilkan 29.5.' },
                { label: 'Pola pemanggilan', code: 'item.baca_data()', detail: 'Loop yang sama memakai method sesuai object aktif.' },
            ],
        },
        options: [
            { label: 'Output 7.1 lalu 29.5', correct: true, feedback: 'Loop memanggil method bernama sama, lalu Python menggunakan implementasi override milik masing-masing object.' },
            { label: 'Output 7.1 dua kali', correct: false, feedback: 'Object SensorSuhu memakai override yang mengembalikan 29.5.' },
            { label: 'Error karena kedua object harus memiliki tipe yang sama', correct: false, feedback: 'Kedua object memiliki method yang dipanggil; pewarisan dari superclass memungkinkan pola ini.' },
        ],
    },
    'method-overriding': {
        kind: 'Kenali overriding',
        prompt: 'Rawa mendefinisikan method info() dengan nama yang sama seperti Ekosistem.info(). Apa yang terjadi saat rawa.info() dipanggil?',
        options: [
            { label: 'Implementasi info() milik Rawa digunakan', correct: true, feedback: 'Method subclass dengan nama yang sama menggantikan implementasi yang diwarisi untuk object Rawa.' },
            { label: 'Python selalu menjalankan kedua method secara otomatis', correct: false, feedback: 'Overriding memilih implementasi method pada class object; memanggil super() perlu ditulis secara eksplisit.' },
            { label: 'Object rawa kehilangan atribut superclass', correct: false, feedback: 'Overriding mengubah perilaku method, bukan menghapus atribut object.' },
        ],
    },
    'duck-typing': {
        kind: 'Pahami konsep',
        prompt: 'Dua class yang tidak berhubungan sama-sama memiliki method baca(). Mengapa keduanya dapat diproses oleh satu loop?',
        visualization: {
            caption: 'Duck typing memakai perilaku yang tersedia pada object',
            layout: 'flow',
            items: [
                { label: 'Object class SensorPH', code: 'sensor_ph.baca()', detail: 'Menghasilkan pembacaan pH.' },
                { label: 'Object class SensorSuhu', code: 'sensor_suhu.baca()', detail: 'Menghasilkan pembacaan suhu.' },
                { label: 'Satu pola pemanggilan', code: 'for sensor in sensors: sensor.baca()', detail: 'Loop meminta perilaku baca() tanpa memeriksa nama class.' },
            ],
        },
        options: [
            { label: 'Karena keduanya harus mewarisi class yang sama', correct: false, feedback: 'Duck typing tidak mensyaratkan superclass bersama.' },
            { label: 'Karena loop memanggil perilaku baca() yang tersedia pada setiap object', correct: true, feedback: 'Duck typing berfokus pada perilaku yang dapat digunakan, bukan hubungan pewarisan atau nama tipe.' },
            { label: 'Karena Python mengubah keduanya menjadi tipe yang sama', correct: false, feedback: 'Python tidak mengubah tipe object; kode memanggil method yang tersedia.' },
        ],
    },
    'membuat-abstract-base-class': {
        kind: 'Pahami kontrak',
        prompt: 'Apa yang terjadi jika program mencoba membuat object dari class ABC yang masih memiliki abstract method belum dipenuhi?',
        code: 'from abc import ABC, abstractmethod\n\nclass Bentuk(ABC):\n    @abstractmethod\n    def hitung_luas(self):\n        pass\n\nbentuk = Bentuk()',
        visualization: {
            caption: 'Kontrak ABC menentukan apakah subclass dapat dibuat menjadi object',
            layout: 'compare',
            items: [
                { label: 'Kontrak', code: 'Bentuk(ABC)', detail: 'Menandai hitung_luas() sebagai kewajiban.' },
                { label: 'Dipenuhi', code: 'Subclass mengisi hitung_luas()', detail: 'Subclass konkret dapat dibuat menjadi object.' },
                { label: 'Belum dipenuhi', code: 'Bentuk()', detail: 'Python menolak instansiasi dengan TypeError.' },
            ],
        },
        options: [
            { label: 'Object berhasil dibuat dan method otomatis kosong', correct: false, feedback: 'Abstract method yang belum dipenuhi mencegah instansiasi normal.' },
            { label: 'Python menolak instansiasi dengan TypeError', correct: true, feedback: 'TypeError menunjukkan subclass belum memenuhi kontrak method abstrak.' },
            { label: 'Python menghapus abstract method', correct: false, feedback: 'Method abstrak tetap menjadi bagian kontrak sampai subclass konkret mengimplementasikannya.' },
        ],
    },
};

function buildConceptDiagram(visualization) {
    const figure = document.createElement('figure');
    figure.className = `material-concept-diagram is-${visualization.layout === 'flow' ? 'flow' : 'comparison'}`;
    const caption = document.createElement('figcaption');
    caption.textContent = visualization.caption;

    const items = document.createElement(visualization.layout === 'flow' ? 'ol' : 'ul');
    items.className = 'material-concept-diagram-items';
    items.style.setProperty('--diagram-columns', String(visualization.items.length));
    items.setAttribute('aria-label', visualization.caption);

    visualization.items.forEach((item) => {
        const step = document.createElement('li');
        step.className = 'material-concept-diagram-item';
        const label = document.createElement('strong');
        label.textContent = item.label;
        const code = document.createElement('code');
        code.textContent = item.code;
        const detail = document.createElement('span');
        detail.textContent = item.detail;
        step.append(label, code, detail);
        items.append(step);
    });

    figure.append(caption, items);
    return figure;
}

function buildLearningCheck(section, config) {
    const slot = section.querySelector('[data-learning-check-slot]');
    if (!slot) return;

    const panel = document.createElement('section');
    panel.className = 'material-learning-check';
    panel.setAttribute('aria-labelledby', `${section.id}-check-title`);

    const label = document.createElement('p');
    label.className = 'material-learning-check-label';
    label.textContent = config.kind;

    const title = document.createElement('h3');
    title.id = `${section.id}-check-title`;
    title.textContent = 'Coba pahami';

    const prompt = document.createElement('p');
    prompt.className = 'material-learning-check-prompt';
    prompt.textContent = config.prompt;

    panel.append(label, title);
    if (config.visualization) panel.append(buildConceptDiagram(config.visualization));
    panel.append(prompt);

    if (config.code) {
        const code = document.createElement('pre');
        code.tabIndex = 0;
        code.setAttribute('aria-label', `Potongan kode untuk aktivitas: ${config.kind}`);
        const codeText = document.createElement('code');
        codeText.className = 'language-python';
        codeText.textContent = config.code;
        code.append(codeText);
        panel.append(code);
        window.OopySyntax?.highlight(codeText);
    }

    const form = document.createElement('form');
    form.className = 'material-learning-check-form';
    const fieldset = document.createElement('fieldset');
    const legend = document.createElement('legend');
    legend.textContent = 'Pilih jawaban, lalu periksa.';
    const choices = document.createElement('div');
    choices.className = 'material-learning-check-choices';

    config.options.forEach((option, index) => {
        const choice = document.createElement('label');
        choice.className = 'material-learning-check-choice';
        const input = document.createElement('input');
        input.type = 'radio';
        input.name = `${section.id}-learning-check`;
        input.value = String(index);
        const text = document.createElement('span');
        text.textContent = option.label;
        choice.append(input, text);
        choices.append(choice);
    });

    fieldset.append(legend, choices);
    const actions = document.createElement('div');
    actions.className = 'material-learning-check-actions';
    const checkButton = document.createElement('button');
    checkButton.type = 'submit';
    checkButton.className = 'material-learning-check-submit';
    checkButton.textContent = 'Periksa jawaban';
    const retryButton = document.createElement('button');
    retryButton.type = 'button';
    retryButton.className = 'material-learning-check-retry';
    retryButton.textContent = 'Coba lagi';
    retryButton.hidden = true;
    actions.append(checkButton, retryButton);

    const feedback = document.createElement('p');
    feedback.className = 'material-learning-check-feedback';
    feedback.setAttribute('role', 'status');
    feedback.setAttribute('aria-live', 'polite');
    feedback.tabIndex = -1;
    feedback.hidden = true;

    form.append(fieldset, actions, feedback);
    panel.append(form);
    slot.replaceChildren(panel);

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        if (form.dataset.checked === 'true') return;
        const selected = form.querySelector('input[type="radio"]:checked');
        if (!selected) {
            feedback.hidden = false;
            feedback.dataset.state = 'notice';
            feedback.textContent = 'Pilih satu jawaban terlebih dahulu.';
            return;
        }

        const answer = config.options[Number(selected.value)];
        form.dataset.checked = 'true';
        fieldset.disabled = true;
        checkButton.hidden = true;
        retryButton.hidden = false;
        feedback.hidden = false;
        feedback.dataset.state = answer.correct ? 'correct' : 'incorrect';
        feedback.textContent = `${answer.correct ? 'Benar.' : 'Belum tepat.'} ${answer.feedback}`;
        feedback.focus({ preventScroll: true });
    });

    retryButton.addEventListener('click', () => {
        delete form.dataset.checked;
        fieldset.disabled = false;
        form.reset();
        feedback.hidden = true;
        feedback.textContent = '';
        delete feedback.dataset.state;
        retryButton.hidden = true;
        checkButton.hidden = false;
        choices.querySelector('input')?.focus();
    });
}

document.querySelectorAll('.oopy-material [data-material-section]').forEach((section) => {
    const config = learningChecks[section.id];
    if (config) buildLearningCheck(section, config);
});
