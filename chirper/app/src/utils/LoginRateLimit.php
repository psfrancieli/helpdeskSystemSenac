<?php

require_once __DIR__ . "/EmailUtils.php";

class LoginRateLimiter
{
    private const MAX_TENTATIVAS = 5;
    private const TEMPO_BLOQUEIO = 900; // 15 minutos

    private function gerarChave(string $email): string
    {
        $email = EmailUtils::normalizar($email);

        return 'login_' . hash('sha256', $email);
    }

    private function iniciarSessao(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['login_rate'])) {
            $_SESSION['login_rate'] = [];
        }
    }

    public function estaBloqueado(string $email): bool
    {
        $this->iniciarSessao();

        $chave = $this->gerarChave($email);

        if (!isset($_SESSION['login_rate'][$chave])) {
            return false;
        }

        $dados = $_SESSION['login_rate'][$chave];

        // Bloqueio já terminou
        if ($dados['bloqueado_ate'] <= time()) {
            unset($_SESSION['login_rate'][$chave]);

            return false;
        }

        return true;
    }

    public function registrarTentativa(string $email): int
    {
        $this->iniciarSessao();

        $chave = $this->gerarChave($email);

        if (!isset($_SESSION['login_rate'][$chave])) {
            $_SESSION['login_rate'][$chave] = [
                'tentativas' => 0,
                'bloqueado_ate' => 0
            ];
        }

        $_SESSION['login_rate'][$chave]['tentativas']++;

        if (
            $_SESSION['login_rate'][$chave]['tentativas']
            >= self::MAX_TENTATIVAS
        ) {
            $_SESSION['login_rate'][$chave]['bloqueado_ate']
                = time() + self::TEMPO_BLOQUEIO;
        }

        return $_SESSION['login_rate'][$chave]['tentativas'];
    }

    public function limpar(string $email): void
    {
        $this->iniciarSessao();

        $chave = $this->gerarChave($email);

        unset($_SESSION['login_rate'][$chave]);
    }

    public function tentativasRestantes(string $email): int
    {
        $this->iniciarSessao();

        $chave = $this->gerarChave($email);

        if (!isset($_SESSION['login_rate'][$chave])) {
            return self::MAX_TENTATIVAS;
        }

        $tentativas = $_SESSION['login_rate'][$chave]['tentativas'];

        return max(
            0,
            self::MAX_TENTATIVAS - $tentativas
        );
    }
}