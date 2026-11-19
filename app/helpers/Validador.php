<?php

namespace app\helpers;

class Validador {
    private array $erros = [];

    public function obrigatorio(string $mvc_campo, mixed $valor, ?string $mensagem = null): static {
        if (empty($valor) && $valor !== '0') {
            $this->erros[$mvc_campo] = $mensagem ?? "O mvc-campo {$mvc_campo} é obrigatório";
        }

        return $this;
    }

    public function temErros(): bool {
        return !empty($this->erros);
    }

    public function getErros(): array {
        return $this->erros;
    }

    /**
     * Validação global robusta de senha:
     * Mínimo de 10 caracteres, com letras maiúsculas, minúsculas, números e caracteres especiais.
     * Retorna a mensagem de erro ou null se a senha for válida.
     */
    public static function validarSenha(string $senha): ?string {
        if (strlen($senha) < 10) {
            return 'A senha deve conter no mínimo 10 caracteres.';
        }
        if (!preg_match('/[A-Z]/', $senha)) {
            return 'A senha deve conter ao menos uma letra maiúscula.';
        }
        if (!preg_match('/[a-z]/', $senha)) {
            return 'A senha deve conter ao menos uma letra minúscula.';
        }
        if (!preg_match('/[0-9]/', $senha)) {
            return 'A senha deve conter ao menos um número.';
        }
        if (!preg_match('/[^a-zA-Z0-9]/', $senha)) {
            return 'A senha deve conter ao menos um caractere especial (ex: !@#$%&*).';
        }
        return null;
    }
}
