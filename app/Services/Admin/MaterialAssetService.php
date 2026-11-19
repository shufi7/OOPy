<?php

namespace App\Services\Admin;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MaterialAssetService
{
    public function choices(): array
    {
        $root = public_path('images/materi');
        $realRoot = realpath($root);
        if ($realRoot === false) {
            return [];
        }
        $assets = [];
        foreach (['photos', 'diagram', 'uploads'] as $folder) {
            $directory = $root.DIRECTORY_SEPARATOR.$folder;
            $realDirectory = realpath($directory);
            if ($realDirectory === false || ! str_starts_with($realDirectory, $realRoot.DIRECTORY_SEPARATOR)) {
                continue;
            }
            foreach (is_dir($directory) ? (glob($directory.DIRECTORY_SEPARATOR.'*') ?: []) : [] as $path) {
                if (! is_file($path) || ! $this->inside($path, $directory)) {
                    continue;
                }
                $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                if (! in_array($extension, $folder === 'diagram' ? ['svg', 'png', 'jpg', 'jpeg', 'webp'] : ['png', 'jpg', 'jpeg', 'webp'], true)) {
                    continue;
                }
                $relative = 'images/materi/'.$folder.'/'.basename($path);
                $assets[] = ['src' => $relative, 'label' => basename($path), 'type' => $folder === 'photos' ? 'photo' : ($folder === 'uploads' ? null : 'diagram')];
            }
        }

        return $assets;
    }

    public function normalize(array $media, bool $allowUploads = false, array &$createdFiles = [], string $sectionIndex = ''): array
    {
        if (count($media) > 2) {
            throw ValidationException::withMessages([$this->field($sectionIndex, 'media') => 'Maksimal dua foto atau diagram dalam satu bagian.']);
        }

        $allowed = collect($this->choices())->keyBy('src');
        $normalized = [];
        foreach (array_values($media) as $index => $visual) {
            if (! is_array($visual)) {
                throw ValidationException::withMessages([$this->field($sectionIndex, "media.$index") => 'Data visual tidak valid.']);
            }
            $type = $visual['type'] ?? '';
            if (! in_array($type, ['photo', 'diagram'], true)) {
                throw ValidationException::withMessages([$this->field($sectionIndex, "media.$index.type") => 'Pilih jenis foto atau diagram.']);
            }
            $src = trim((string) ($visual['src'] ?? ''));
            $upload = $visual['upload'] ?? null;
            if ($upload instanceof UploadedFile && $upload->isValid()) {
                if (! $allowUploads) {
                    throw ValidationException::withMessages([$this->field($sectionIndex, "media.$index.upload") => 'Simpan draf terlebih dahulu untuk mengunggah visual.']);
                }
                $src = $this->storeImage($upload, $type, $index, $createdFiles, $sectionIndex);
            } elseif (! $allowed->has($src)) {
                throw ValidationException::withMessages([$this->field($sectionIndex, "media.$index.src") => 'Pilih aset lokal yang tersedia. URL dan path bebas tidak diizinkan.']);
            } elseif ($allowed[$src]['type'] !== null && $allowed[$src]['type'] !== $type) {
                throw ValidationException::withMessages([$this->field($sectionIndex, "media.$index.src") => 'Jenis aset tidak sesuai dengan pilihan.']);
            }

            $caption = trim((string) ($visual['caption'] ?? ''));
            $alt = trim((string) ($visual['alt'] ?? ''));
            if ($caption === '' || $alt === '') {
                throw ValidationException::withMessages([$this->field($sectionIndex, "media.$index.caption") => 'Alt text dan caption wajib diisi.']);
            }
            $entry = [
                'type' => $type,
                'src' => $src,
                'alt' => $alt,
                'caption' => $caption,
            ];
            foreach (['source_label', 'source_url', 'license_label', 'license_url'] as $field) {
                $value = trim((string) ($visual[$field] ?? ''));
                if ($value !== '') {
                    if (str_ends_with($field, '_url') && ! $this->safeHttpsUrl($value)) {
                        throw ValidationException::withMessages([$this->field($sectionIndex, "media.$index.$field") => 'Tautan sumber dan lisensi harus menggunakan HTTPS.']);
                    }
                    $entry[$field] = $value;
                }
            }
            if ($type === 'photo' && (empty($entry['source_label']) || empty($entry['source_url']))) {
                throw ValidationException::withMessages([$this->field($sectionIndex, "media.$index.source_label") => 'Foto wajib mencantumkan kredit dan URL sumber.']);
            }
            $normalized[] = $entry;
        }

        return $normalized;
    }

    private function storeImage(UploadedFile $file, string $type, int $index, array &$createdFiles, string $sectionIndex): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $mime = $file->getMimeType();
        $mimeExtensions = ['image/jpeg' => ['jpg', 'jpeg'], 'image/png' => ['png'], 'image/webp' => ['webp']];
        $size = $file->getSize() ?: 0;
        $dimensions = @getimagesize($file->getRealPath());
        if (! $file->isValid() || ! isset($mimeExtensions[$mime]) || ! in_array($extension, $mimeExtensions[$mime], true)
            || $size > 5 * 1024 * 1024 || ! $dimensions || $dimensions[0] > 6000 || $dimensions[1] > 6000) {
            throw ValidationException::withMessages([$this->field($sectionIndex, "media.$index.upload") => 'Unggah JPEG, PNG, atau WebP yang valid (maksimal 5 MB dan 6000×6000 piksel). SVG tidak dapat diunggah.']);
        }
        $directory = public_path('images/materi/uploads');
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw ValidationException::withMessages([$this->field($sectionIndex, "media.$index.upload") => 'Folder penyimpanan visual tidak dapat dibuat.']);
        }
        $realRoot = realpath(public_path('images/materi'));
        $realDirectory = realpath($directory);
        if ($realRoot === false || $realDirectory === false || ! str_starts_with($realDirectory, $realRoot.DIRECTORY_SEPARATOR)) {
            throw ValidationException::withMessages([$this->field($sectionIndex, "media.$index.upload") => 'Folder upload berada di luar direktori aset yang diizinkan.']);
        }
        $filename = Str::uuid().'.'.$extension;
        $path = $directory.DIRECTORY_SEPARATOR.$filename;
        $file->move($directory, $filename);
        $createdFiles[] = $path;

        return 'images/materi/uploads/'.$filename;
    }

    private function safeHttpsUrl(string $url): bool
    {
        $parts = parse_url($url);

        return is_array($parts) && strtolower($parts['scheme'] ?? '') === 'https' && ! empty($parts['host']) && ! isset($parts['user']);
    }

    private function inside(string $path, string $directory): bool
    {
        $realPath = realpath($path);
        $realDirectory = realpath($directory);

        return $realPath !== false && $realDirectory !== false && str_starts_with($realPath, $realDirectory.DIRECTORY_SEPARATOR);
    }

    private function field(string $sectionIndex, string $field): string
    {
        return $sectionIndex === '' ? $field : "sections.$sectionIndex.$field";
    }
}
