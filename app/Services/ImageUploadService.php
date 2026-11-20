<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class ImageUploadService
{
    public function store(array $file): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) { throw new RuntimeException('Falha no upload.'); }
        if ((int) $file['size'] > 5 * 1024 * 1024) { throw new RuntimeException('Imagem excede 5 MB.'); }
        $tmp = (string) $file['tmp_name'];
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmp);
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
        if (!isset($allowed[$mime]) || getimagesize($tmp) === false) { throw new RuntimeException('Envie uma imagem JPG ou PNG válida.'); }
        $name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
        $destination = BASE_PATH . '/storage/uploads/' . $name;
        $source = $mime === 'image/png' ? imagecreatefrompng($tmp) : imagecreatefromjpeg($tmp);
        if (!$source) { throw new RuntimeException('Não foi possível processar a imagem.'); }
        if ($mime === 'image/png') { imagealphablending($source, false); imagesavealpha($source, true); imagepng($source, $destination, 8); }
        else { imagejpeg($source, $destination, 88); }
        imagedestroy($source);
        @chmod($destination, 0640);
        return $name;
    }
}
