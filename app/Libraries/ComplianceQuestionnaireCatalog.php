<?php

namespace App\Libraries;

class ComplianceQuestionnaireCatalog
{
  public static function defaults(): array
  {
    return [
      [
        'slug' => 'supervisor-behaviour-survey',
        'title' => 'Supervisory Skills Training (SST)',
        'subtitle' => 'Evaluasi Pra dan Pasca Pelatihan - Evaluasi Perilaku',
        'description' => 'Instruksi: Diisi oleh supervisor yang telah dinominasikan untuk mengikuti atau telah menyelesaikan SST. Form ini diisi satu minggu sebelum pelatihan atau enam bulan setelah pelatihan selesai. Pilih satu jawaban yang paling sesuai untuk setiap pertanyaan.',
        'sort_order' => 10,
        'questions' => [
          ['section_label' => 'Informasi Umum', 'question_code' => '1', 'sort_order' => 10, 'question_text' => 'Tanggal pengisian formulir', 'answer_type' => 'date', 'placeholder' => 'Pilih tanggal'],
          ['section_label' => 'Informasi Umum', 'question_code' => '2', 'sort_order' => 20, 'question_text' => 'Status pelatihan SST', 'answer_type' => 'radio', 'options' => ['Telah dinominasikan untuk mengikuti SST', 'Telah mengikuti SST']],
          ['section_label' => 'Informasi Umum', 'question_code' => '3', 'sort_order' => 30, 'question_text' => 'Tanggal atau bulan pelatihan SST', 'answer_type' => 'text', 'placeholder' => 'Contoh: 12 Januari 2026 atau Januari 2026'],
          ['section_label' => 'Informasi Umum', 'question_code' => '4', 'sort_order' => 40, 'question_text' => 'Jenis kelamin', 'answer_type' => 'radio', 'options' => ['Perempuan', 'Laki-laki']],
          ['section_label' => 'Informasi Umum', 'question_code' => '5', 'sort_order' => 50, 'question_text' => 'Umur (dalam tahun)', 'answer_type' => 'radio', 'options' => ['18-24', '25-34', '35-54', '55 atau lebih']],
          ['section_label' => 'Informasi Umum', 'question_code' => '6', 'sort_order' => 60, 'question_text' => 'Status perkawinan', 'answer_type' => 'radio', 'options' => ['Single, tidak pernah menikah', 'Menikah tanpa anak', 'Menikah dan memiliki anak', 'Bercerai', 'Janda/Duda']],
          ['section_label' => 'Informasi Umum', 'question_code' => '7', 'sort_order' => 70, 'question_text' => 'Tingkat pendidikan tertinggi (selesai)', 'answer_type' => 'radio', 'options' => ['SD', 'SMP', 'SMA', 'Akademi/Universitas']],
          ['section_label' => 'Informasi Umum', 'question_code' => '8', 'sort_order' => 80, 'question_text' => 'Berapa lama anda bekerja di pabrik sebagai supervisor?', 'answer_type' => 'radio', 'options' => ['Di bawah satu tahun', '1 sampai 5 tahun', '6 sampai 10 tahun', 'Lebih dari 10 tahun']],
          ['section_label' => 'Evaluasi Perilaku', 'question_code' => '9', 'sort_order' => 90, 'question_text' => 'Ketika anda akan melewati tenggat waktu, anda akan...', 'answer_type' => 'radio', 'options' => ['Pasrah menerima kenyataan dan minta perpanjangan waktu', 'Mencari tahu penyebab utama keterlambatan dan mencari saran perbaikan', 'Meminta pekerja untuk lembur dan mencapai target sesuai dengan tenggat waktu']],
          ['section_label' => 'Evaluasi Perilaku', 'question_code' => '10', 'sort_order' => 100, 'question_text' => 'Ketika seorang pekerja tidak dibayar untuk sebagian kerja lembur, anda akan...', 'answer_type' => 'radio', 'options' => ['Menjelaskan bahwa pekerja mendapatkan apa yang mereka setujui pada saat perekrutan', 'Eskalasi kepada pihak management', 'Ingin membantu pekerja, namun saya tahu bahwa ini adalah tanggung jawab Personalia/HR']],
          ['section_label' => 'Evaluasi Perilaku', 'question_code' => '11', 'sort_order' => 110, 'question_text' => 'Apa yang akan anda lakukan ketika seorang pekerja terlambat secara terus menerus sebanyak 3 sampai 4 hari?', 'answer_type' => 'radio', 'options' => ['Meminta management (Personalia/HR) untuk mengeluarkan SP', 'Meminta pekerja untuk tidak datang terlambat', 'Berdiskusi dengan pekerja dan mencoba mencari solusi untuk menjaga disiplin']],
          ['section_label' => 'Evaluasi Perilaku', 'question_code' => '12', 'sort_order' => 120, 'question_text' => 'Apa yang akan anda lakukan jika melihat kepadatan atau suhu yang tinggi di suatu area kerja?', 'answer_type' => 'radio', 'options' => ['Eskalasi kepada management atau bagian pemeliharaan (maintenance)', 'Adalah normal jika pabrik memiliki kondisi seperti itu', 'Akan menyelesaikan masalah tersebut jika pekerja mengeluh']],
          ['section_label' => 'Evaluasi Perilaku', 'question_code' => '13', 'sort_order' => 130, 'question_text' => 'Apa yang akan anda lakukan jika anda percaya bahwa anda berlaku adil, tetapi beberapa pekerja berpikir anda berat sebelah?', 'answer_type' => 'radio', 'options' => ['Saya akan mencoba menjelaskan kepada pekerja tentang perilaku saya', 'Saya akan terus berperilaku sama, mengetahui bahwa pada kenyataannya saya tidak berat sebelah', 'Saya akan mencoba mengelola perilaku saya yang mungkin memberi kesan berat sebelah']],
          ['section_label' => 'Evaluasi Perilaku', 'question_code' => '14', 'sort_order' => 140, 'question_text' => 'Ketika anda memiliki tenggat waktu yang ketat dan seorang pekerja meminta cuti panjang, anda akan...', 'answer_type' => 'radio', 'options' => ['Prioritaskan pekerjaan dan pastikan bahwa seluruh pekerjaan selesai sebelum pekerja cuti', 'Negosiasi untuk menjadwal ulang atau mengurangi hari cuti', 'Menyetujui permintaan cuti']],
          ['section_label' => 'Evaluasi Perilaku', 'question_code' => '15', 'sort_order' => 150, 'question_text' => 'Daftar tugas bagi saya adalah...', 'answer_type' => 'radio', 'options' => ['Saya menerima tugas langsung dan menyelesaikannya', 'Aktifitas harian', 'Aktifitas sekali-kali']],
          ['section_label' => 'Evaluasi Perilaku', 'question_code' => '16', 'sort_order' => 160, 'question_text' => 'Untuk membangun hubungan yang baik, saya percaya...', 'answer_type' => 'radio', 'options' => ['Sangat penting untuk memahami orang lain', 'Sangat penting untuk menyetujui sudut pandang orang lain', 'Sangat penting untuk mengekspresikan diri dengan baik']],
          ['section_label' => 'Evaluasi Perilaku', 'question_code' => '17', 'sort_order' => 170, 'question_text' => 'Saat menugaskan pekerjaan kepada pekerja, saya biasanya tahu bahwa...', 'answer_type' => 'radio', 'options' => ['Pekerja memahami saya dengan cukup baik untuk melakukan pekerjaannya', 'Saya perlu memberi waktu untuk menjelaskan pekerjaan itu', 'Jika pekerja tidak memahami tugas yang diberikan, maka saya mungkin harus melakukan sebagian dari pekerjaan itu']],
          ['section_label' => 'Evaluasi Perilaku', 'question_code' => '18', 'sort_order' => 180, 'question_text' => 'Ketika ada konflik di antara dua orang pekerja, saya akan...', 'answer_type' => 'radio', 'options' => ['Memfasilitasi diskusi antara keduanya dan membantu mereka menemukan solusi', 'Mempercayakan kepada pekerja untuk mendapatkan solusi mereka sendiri', 'Campur tangan dan memberikan keputusan berdasarkan pengalaman saya']],
          ['section_label' => 'Diisi Surveyor', 'question_code' => '18a', 'sort_order' => 190, 'question_text' => 'Nama pabrik', 'answer_type' => 'text', 'placeholder' => 'Isi nama pabrik'],
          ['section_label' => 'Diisi Surveyor', 'question_code' => '18b', 'sort_order' => 200, 'question_text' => 'ID Pabrik', 'answer_type' => 'text', 'placeholder' => 'Isi ID pabrik'],
          ['section_label' => 'Diisi Surveyor', 'question_code' => '18c', 'sort_order' => 210, 'question_text' => 'Negara', 'answer_type' => 'text', 'placeholder' => 'Isi negara'],
          ['section_label' => 'Diisi Surveyor', 'question_code' => '18d', 'sort_order' => 220, 'question_text' => 'ID Pelatihan', 'answer_type' => 'text', 'placeholder' => 'Isi ID pelatihan'],
        ],
      ],
      [
        'slug' => 'worker-behaviour-survey',
        'title' => 'Supervisory Skills Training (SST)',
        'subtitle' => 'Evaluasi Pra dan Pasca Pelatihan - Evaluasi Perilaku Pekerja',
        'description' => 'Instruksi: Diisi oleh pekerja yang supervisornya dinominasikan atau telah menyelesaikan SST. Pilih satu jawaban yang paling sesuai untuk setiap pertanyaan. Tidak ada jawaban benar atau salah.',
        'sort_order' => 20,
        'questions' => [
          ['section_label' => 'Evaluasi Perilaku', 'question_code' => '1', 'sort_order' => 10, 'question_text' => 'Ketika waktu yang ditentukan untuk anda hampir habis, supervisor anda akan...', 'answer_type' => 'radio', 'options' => ['Meminta perpanjangan waktu kepada manajemen', 'Meminta kerja lembur dan menyelesaikan pekerjaan sesuai waktu yang telah disepakati', 'Konsultasikan dengan kami untuk mencari penyebab keterlambatan serta solusinya']],
          ['section_label' => 'Evaluasi Perilaku', 'question_code' => '2', 'sort_order' => 20, 'question_text' => 'Jika saya atau pekerja lain tidak dibayar penuh upah lemburnya, maka supervisor saya akan...', 'answer_type' => 'radio', 'options' => ['Melaporkan ke manajemen', 'Mengarahkan kami ke HR', 'Meminta saya untuk fokus pada pekerjaan saya']],
          ['section_label' => 'Evaluasi Perilaku', 'question_code' => '3', 'sort_order' => 30, 'question_text' => 'Jika saya atau pekerja lain terlambat bekerja, maka supervisor saya akan...', 'answer_type' => 'radio', 'options' => ['Mengingatkan untuk disiplin dan memperingatkan akibatnya', 'Mengecek alasan keterlambatan dan memberikan solusi', 'Meminta untuk tepat waktu ke depannya']],
          ['section_label' => 'Evaluasi Perilaku', 'question_code' => '4', 'sort_order' => 40, 'question_text' => 'Pada saat pekerjaan sedang sibuk jika saya atau rekan kerja saya meminta cuti, supervisor saya biasanya akan...', 'answer_type' => 'radio', 'options' => ['Mengutip kelebihan beban kerja dan akan menolak cuti', 'Menyetujui cuti dengan mudah', 'Menegosiasikan untuk menjadwal ulang cutinya']],
          ['section_label' => 'Evaluasi Perilaku', 'question_code' => '5', 'sort_order' => 50, 'question_text' => 'Selama diskusi, supervisor saya kebanyakan akan...', 'answer_type' => 'radio', 'options' => ['Mendengarkan pendapat saya dan mendiskusikan sebelum menyetujuinya', 'Memberikan pendapat beliau', 'Setuju dengan pendapat saya']],
          ['section_label' => 'Evaluasi Perilaku', 'question_code' => '6', 'sort_order' => 60, 'question_text' => 'Saya mendengar supervisor saya berbicara keras kepada pekerja lain...', 'answer_type' => 'radio', 'options' => ['Sesekali', 'Sering', 'Jarang']],
          ['section_label' => 'Evaluasi Perilaku', 'question_code' => '7', 'sort_order' => 70, 'question_text' => 'Ketika ada perselisihan di antara dua pekerja, supervisor saya akan...', 'answer_type' => 'radio', 'options' => ['Tidak ikut campur dalam perselisihan dan mempercayakan pekerjanya untuk menyelesaikan masalah sendiri', 'Memberikan solusi berdasarkan pengalamannya', 'Memfasilitasi diskusi antara kedua pekerja dan mencari solusi']],
          ['section_label' => 'Evaluasi Perilaku', 'question_code' => '8', 'sort_order' => 80, 'question_text' => 'Secara keseluruhan saya pikir supervisor saya adalah seorang yang...', 'answer_type' => 'radio', 'options' => ['Tegas', 'Seimbang', 'Cukup toleran']],
          ['section_label' => 'Diisi Surveyor', 'question_code' => '9', 'sort_order' => 90, 'question_text' => 'Apakah survey ini pra atau pasca training?', 'answer_type' => 'radio', 'options' => ['Pre', 'Post']],
          ['section_label' => 'Diisi Surveyor', 'question_code' => '10', 'sort_order' => 100, 'question_text' => 'Nama pabrik', 'answer_type' => 'text', 'placeholder' => 'Isi nama pabrik'],
          ['section_label' => 'Diisi Surveyor', 'question_code' => '11', 'sort_order' => 110, 'question_text' => 'NIK Pekerja', 'answer_type' => 'text', 'placeholder' => 'Isi NIK pekerja'],
          ['section_label' => 'Diisi Surveyor', 'question_code' => '12', 'sort_order' => 120, 'question_text' => 'Negara', 'answer_type' => 'text', 'placeholder' => 'Isi negara'],
          ['section_label' => 'Diisi Surveyor', 'question_code' => '13', 'sort_order' => 130, 'question_text' => 'NIK Training', 'answer_type' => 'text', 'placeholder' => 'Isi NIK training'],
          ['section_label' => 'Diisi Surveyor', 'question_code' => '14', 'sort_order' => 140, 'question_text' => 'Tanggal pengisian form', 'answer_type' => 'date', 'placeholder' => 'Pilih tanggal'],
        ],
      ],
    ];
  }
}
