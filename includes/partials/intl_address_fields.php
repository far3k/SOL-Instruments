<?php

declare(strict_types=1);

/** @var string $intl_id_prefix e.g. shop | rent */
/** @var array<string, string> $intl_values */

if (!isset($intl_id_prefix) || !is_string($intl_id_prefix)) {
    $intl_id_prefix = "addr";
}
if (!isset($intl_values) || !is_array($intl_values)) {
    $intl_values = [];
}
$intl_values = sol_intl_address_normalize($intl_values);
$h = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES, "UTF-8");
$id = static fn (string $s): string => $h($intl_id_prefix . "-intl-" . $s);
$nm = static fn (string $k): string => "intl_addr[" . $k . "]";
?>

<div class="sol-intl-address-fields border rounded-3 p-3 bg-white bg-opacity-75">
    <p class="small text-muted mb-3 mb-md-2">International address format (same fields for shop delivery and rent courier).</p>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="<?= $id("recipient") ?>">Full name <span class="text-danger">*</span></label>
            <input type="text" class="form-control form-control-sm rounded-3" name="<?= $h($nm("recipient_name")) ?>" id="<?= $id("recipient") ?>" value="<?= $h($intl_values["recipient_name"]) ?>" maxlength="<?= (int)sol_intl_address_max_len("recipient_name") ?>" autocomplete="name">
        </div>
        <div class="col-md-6">
            <label class="form-label" for="<?= $id("org") ?>">Organization <span class="text-muted fw-normal">(optional)</span></label>
            <input type="text" class="form-control form-control-sm rounded-3" name="<?= $h($nm("organization")) ?>" id="<?= $id("org") ?>" value="<?= $h($intl_values["organization"]) ?>" maxlength="<?= (int)sol_intl_address_max_len("organization") ?>" autocomplete="organization">
        </div>
        <div class="col-12">
            <label class="form-label" for="<?= $id("line1") ?>">Address line 1 <span class="text-danger">*</span></label>
            <input type="text" class="form-control form-control-sm rounded-3" name="<?= $h($nm("address_line1")) ?>" id="<?= $id("line1") ?>" value="<?= $h($intl_values["address_line1"]) ?>" maxlength="<?= (int)sol_intl_address_max_len("address_line1") ?>" autocomplete="address-line1">
        </div>
        <div class="col-12">
            <label class="form-label" for="<?= $id("line2") ?>">Address line 2 <span class="text-muted fw-normal">(apt, suite, floor…)</span></label>
            <input type="text" class="form-control form-control-sm rounded-3" name="<?= $h($nm("address_line2")) ?>" id="<?= $id("line2") ?>" value="<?= $h($intl_values["address_line2"]) ?>" maxlength="<?= (int)sol_intl_address_max_len("address_line2") ?>" autocomplete="address-line2">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="<?= $id("locality") ?>">City / town <span class="text-danger">*</span></label>
            <input type="text" class="form-control form-control-sm rounded-3" name="<?= $h($nm("locality")) ?>" id="<?= $id("locality") ?>" value="<?= $h($intl_values["locality"]) ?>" maxlength="<?= (int)sol_intl_address_max_len("locality") ?>" autocomplete="address-level2">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="<?= $id("admin") ?>">State / province / region</label>
            <input type="text" class="form-control form-control-sm rounded-3" name="<?= $h($nm("admin_area")) ?>" id="<?= $id("admin") ?>" value="<?= $h($intl_values["admin_area"]) ?>" maxlength="<?= (int)sol_intl_address_max_len("admin_area") ?>" autocomplete="address-level1">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="<?= $id("postal") ?>">Postal / ZIP code <span class="text-danger">*</span></label>
            <input type="text" class="form-control form-control-sm rounded-3" name="<?= $h($nm("postal_code")) ?>" id="<?= $id("postal") ?>" value="<?= $h($intl_values["postal_code"]) ?>" maxlength="<?= (int)sol_intl_address_max_len("postal_code") ?>" autocomplete="postal-code">
        </div>
        <div class="col-md-6">
            <label class="form-label" for="<?= $id("country") ?>">Country <span class="text-danger">*</span></label>
            <select class="form-select form-select-sm rounded-3" name="<?= $h($nm("country_code")) ?>" id="<?= $id("country") ?>" autocomplete="country">
                <option value=""><?= $h("— Select —") ?></option>
                <?php foreach (sol_intl_countries() as $code => $countryName): ?>
                    <option value="<?= $h($code) ?>" <?= $intl_values["country_code"] === $code ? " selected" : "" ?>><?= $h($countryName) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="<?= $id("phone") ?>">Phone <span class="text-muted fw-normal">(incl. country code)</span></label>
            <input type="tel" class="form-control form-control-sm rounded-3" name="<?= $h($nm("phone")) ?>" id="<?= $id("phone") ?>" value="<?= $h($intl_values["phone"]) ?>" maxlength="<?= (int)sol_intl_address_max_len("phone") ?>" autocomplete="tel" placeholder="+49 …">
        </div>
        <div class="col-12">
            <label class="form-label" for="<?= $id("instr") ?>">Delivery instructions <span class="text-muted fw-normal">(optional)</span></label>
            <textarea class="form-control form-control-sm rounded-3" name="<?= $h($nm("instructions")) ?>" id="<?= $id("instr") ?>" rows="2" maxlength="<?= (int)sol_intl_address_max_len("instructions") ?>" placeholder="Door code, time window, buzzer name…"><?= $h($intl_values["instructions"]) ?></textarea>
        </div>
    </div>
</div>
