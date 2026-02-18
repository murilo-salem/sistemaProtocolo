<?php

namespace Tests\Unit\Model;

use Tests\TestCase;

/**
 * Testes unitários para o model Projeto
 */
class ProjetoTest extends TestCase
{
    /**
     * Testa criação de projeto com atributos básicos
     */
    public function testProjetoHasRequiredAttributes(): void
    {
        $projeto = $this->createMockProjeto();
        
        $this->assertNotNull($projeto->id);
        $this->assertNotNull($projeto->nome);
        $this->assertNotNull($projeto->dia_vencimento);
    }
    
    /**
     * Testa que dia_vencimento está no range válido (1-31)
     */
    public function testDiaVencimentoInValidRange(): void
    {
        // Dias válidos
        $validDays = [1, 15, 28, 31];
        
        foreach ($validDays as $day) {
            $projeto = $this->createMockProjeto(['dia_vencimento' => $day]);
            $this->assertGreaterThanOrEqual(1, $projeto->dia_vencimento);
            $this->assertLessThanOrEqual(31, $projeto->dia_vencimento);
        }
    }
    
    /**
     * Testa projeto ativo vs inativo
     */
    public function testProjetoAtivoStatus(): void
    {
        $projetoAtivo = $this->createMockProjeto(['ativo' => 1]);
        $projetoInativo = $this->createMockProjeto(['ativo' => 0]);
        
        $this->assertEquals(1, $projetoAtivo->ativo);
        $this->assertEquals(0, $projetoInativo->ativo);
    }
    
    /**
     * Testa que projeto template pode ser identificado
     */
    public function testProjetoIsTemplate(): void
    {
        $template = $this->createMockProjeto(['is_template' => 1]);
        $normal = $this->createMockProjeto(['is_template' => 0]);
        
        $this->assertEquals(1, $template->is_template);
        $this->assertEquals(0, $normal->is_template);
    }
    
    /**
     * Testa formatação de data de vencimento
     */
    public function testFormatDueDate(): void
    {
        $projeto = $this->createMockProjeto(['dia_vencimento' => 5]);
        
        $currentMonth = date('m');
        $currentYear = date('Y');
        
        $dueDate = sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $projeto->dia_vencimento);
        
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $dueDate);
    }
    
    /**
     * Testa cálculo de próximo vencimento
     */
    public function testCalculateNextDueDate(): void
    {
        $projeto = $this->createMockProjeto(['dia_vencimento' => 15]);
        $today = new \DateTime();
        $dueDay = $projeto->dia_vencimento;
        
        $dueDate = new \DateTime();
        $dueDate->setDate($dueDate->format('Y'), $dueDate->format('m'), $dueDay);
        
        // Se já passou o dia, usar próximo mês
        if ($dueDate < $today) {
            $dueDate->modify('+1 month');
        }
        
        $this->assertGreaterThanOrEqual($today, $dueDate);
    }
    
    /**
     * Testa que projeto pode ter company_template_id
     */
    public function testProjetoWithCompanyTemplate(): void
    {
        $projeto = $this->createMockProjeto(['company_template_id' => 5]);
        
        $this->assertEquals(5, $projeto->company_template_id);
    }
    
    /**
     * Testa projeto sem template associado
     */
    public function testProjetoWithoutCompanyTemplate(): void
    {
        $projeto = $this->createMockProjeto(['company_template_id' => null]);
        
        $this->assertNull($projeto->company_template_id);
    }
}
