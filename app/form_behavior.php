<?php
declare(strict_types=1);

/** Project-wide destinations after a normal form save. */
function svmFormBehavior($value, bool $strict = false): array
{
    if ($strict && !is_array($value)) throw new InvalidArgumentException('Neplatné chování po uložení.');
    $out = [];
    foreach (['trees', 'areas'] as $kind) {
        foreach (['create' => 'form', 'edit' => 'card'] as $action => $default) {
            $choice = is_array($value[$kind] ?? null) ? ($value[$kind][$action] ?? $default) : $default;
            if (!in_array($choice, ['form', 'card'], true)) {
                if ($strict) throw new InvalidArgumentException('Neplatná volba po uložení.');
                $choice = $default;
            }
            $out[$kind][$action] = $choice;
        }
    }
    return $out;
}

function svmProjectFormBehavior(mysqli $db, int $project): array
{
    $q = $db->prepare('SELECT settings_json FROM svm_form_settings WHERE project_id=?');
    $q->bind_param('i', $project);
    $q->execute();
    $row = $q->get_result()->fetch_assoc();
    $q->close();
    $settings = json_decode($row['settings_json'] ?? '{}', true);
    return svmFormBehavior($settings['after_save'] ?? []);
}
