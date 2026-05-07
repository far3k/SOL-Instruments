<?php

# Image upload helpers for avatars/product photos; not a public endpoint.

if (count(get_included_files()) === 1) {
    header("Location: login.php");
    exit;
}

function picturesDir(): string
{
    return __DIR__ . DIRECTORY_SEPARATOR . "pictures" . DIRECTORY_SEPARATOR;
}

function fileUpload($picture)
{
    if ($picture["error"] === 4) {
        return ["avatar.png", "No file selected. Default avatar used."];
    }

    $checkIfImage = getimagesize($picture["tmp_name"]);
    if ($checkIfImage === false) {
        return [null, "Uploaded file is not a valid image."];
    }

    $extension = strtolower(pathinfo($picture["name"], PATHINFO_EXTENSION));
    $fileName = uniqid("img_", true) . "." . $extension;
    $dir = picturesDir();
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $uploadPath = $dir . $fileName;

    if (move_uploaded_file($picture["tmp_name"], $uploadPath)) {
        return [$fileName, "Image uploaded successfully."];
    }

    return [null, "Image upload failed."];
}

/** Product image upload; default filename when no file selected. */
function productImageUpload(array $picture): array
{
    if ($picture["error"] === 4) {
        return ["product.jpg", "Default product image will be used."];
    }

    $checkIfImage = getimagesize($picture["tmp_name"]);
    if ($checkIfImage === false) {
        return [null, "Uploaded file is not a valid image."];
    }

    $extension = strtolower(pathinfo($picture["name"], PATHINFO_EXTENSION));
    $fileName = uniqid("img_", true) . "." . $extension;
    $dir = picturesDir();
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $uploadPath = $dir . $fileName;

    if (move_uploaded_file($picture["tmp_name"], $uploadPath)) {
        return [$fileName, "Image uploaded successfully."];
    }

    return [null, "Image upload failed."];
}

/** Instrument image; default filename when no file selected. */
function instrumentImageUpload(array $picture): array
{
    if ($picture["error"] === 4) {
        return ["instrument.jpg", "Default instrument image will be used."];
    }

    $checkIfImage = getimagesize($picture["tmp_name"]);
    if ($checkIfImage === false) {
        return [null, "Uploaded file is not a valid image."];
    }

    $extension = strtolower(pathinfo($picture["name"], PATHINFO_EXTENSION));
    $fileName = uniqid("img_", true) . "." . $extension;
    $dir = picturesDir();
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $uploadPath = $dir . $fileName;

    if (move_uploaded_file($picture["tmp_name"], $uploadPath)) {
        return [$fileName, "Image uploaded successfully."];
    }

    return [null, "Image upload failed."];
}
