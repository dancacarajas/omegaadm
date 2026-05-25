<?php

namespace Tests\Unit;

use App\Services\ConfiguracaoZimbraEmailService;
use RuntimeException;
use Tests\TestCase;

class ConfiguracaoZimbraEmailServiceTest extends TestCase
{
    public function test_mensagem_inclui_erro_tecnico_real(): void
    {
        $service = new ConfiguracaoZimbraEmailService;
        $inner = new RuntimeException('Connection timed out');
        $outer = new RuntimeException('Unable to connect with STARTTLS', 0, $inner);

        $msg = $service->mensagemErroParaUsuario($outer);

        $this->assertStringContainsString('Erro técnico:', $msg);
        $this->assertStringContainsString('Connection timed out', $msg);
        $this->assertStringContainsString('smtp.omegaservice.com.br', $msg);
        $this->assertStringNotContainsString('webmail.omegaservice.com.br', $msg);
    }

    public function test_extrair_mensagem_tecnica_com_cadeia(): void
    {
        $service = new ConfiguracaoZimbraEmailService;
        $e = new RuntimeException('Authentication failed', 0, new RuntimeException('535 5.7.8 Error'));

        $this->assertStringContainsString('Authentication failed', $service->extrairMensagemTecnica($e));
        $this->assertStringContainsString('535', $service->extrairMensagemTecnica($e));
    }
}
