<?php
require_once __DIR__.'/../app/form_behavior.php';
function check($actual, $expected): void {
    if ($actual !== $expected) throw new RuntimeException('Unexpected form behavior');
}
$defaults = ['trees'=>['create'=>'form','edit'=>'card'], 'areas'=>['create'=>'form','edit'=>'card']];
check(svmFormBehavior(null), $defaults);
foreach (['trees', 'areas'] as $kind) {
    foreach (['create', 'edit'] as $action) {
        foreach (['form', 'card'] as $choice) {
            $expected = $defaults;
            $expected[$kind][$action] = $choice;
            check(svmFormBehavior([$kind=>[$action=>$choice]], true), $expected);
        }
    }
}
check(svmFormBehavior(['trees'=>['create'=>'invalid']]), $defaults);
try {
    svmFormBehavior(['areas'=>['edit'=>'https://example.test']], true);
    throw new RuntimeException('Invalid destination accepted');
} catch (InvalidArgumentException $expected) {}
echo "PASS form destinations, defaults and invalid settings\n";
