<?php
declare(strict_types=1);
// Tokens are credentials: private storage only, never logs or public assets.
function svmShareTokenDirectory(): string { return dirname(__DIR__).'/private/storage/tree_share_tokens'; }
function svmStoreShareToken(string $token): void {
    if (!preg_match('/^[a-f0-9]{64}$/D',$token)) throw new InvalidArgumentException('Invalid share token');
    $dir=svmShareTokenDirectory();
    if (!is_dir($dir) && !mkdir($dir,0700,true) && !is_dir($dir)) throw new RuntimeException('Odkaz nelze bezpečně uložit.');
    $path=$dir.'/'.hash('sha256',$token).'.token';
    $old=umask(0077);
    try { if (file_put_contents($path,$token,LOCK_EX)!==64) throw new RuntimeException('Odkaz nelze bezpečně uložit.'); }
    finally { umask($old); }
}
function svmReadShareToken(string $hash): ?string {
    if (!preg_match('/^[a-f0-9]{64}$/D',$hash)) return null;
    $path=svmShareTokenDirectory().'/'.$hash.'.token';
    if (!is_file($path)) return null;
    $token=file_get_contents($path);
    return is_string($token) && preg_match('/^[a-f0-9]{64}$/D',$token) && hash_equals($hash,hash('sha256',$token)) ? $token : null;
}
