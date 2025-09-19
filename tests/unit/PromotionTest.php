<?php

use PHPUnit\Framework\TestCase;

class PromotionTest extends TestCase
{
    private $module;
    
    protected function setUp(): void
    {
        $this->module = new PromotionsCountdown();
    }
    
    public function testCountdownPromotionIsBest()
    {
        // Test: promozione countdown 15% vs nessuna altra promozione
        $active_promotions = [
            [
                'id_promotion' => 1,
                'name' => 'Test Promotion',
                'discount_percent' => 15.0,
                'start_date' => '2024-01-01 00:00:00',
                'end_date' => '2024-12-31 23:59:59',
                'created_date' => '2024-01-01 00:00:00'
            ]
        ];
        
        $result = $this->module->getProductDiscount(1, $active_promotions);
        
        $this->assertNotNull($result);
        $this->assertEquals('countdown', $result['type']);
        $this->assertEquals(15.0, $result['discount_percent']);
    }
    
    public function testSpecificPriceIsBetterThanCountdown()
    {
        // Test: SpecificPrice 40% vs Countdown 15%
        // Simula che SpecificPrice::getByProductId restituisca una promozione del 40%
        
        $active_promotions = [
            [
                'id_promotion' => 1,
                'name' => 'Test Promotion',
                'discount_percent' => 15.0,
                'start_date' => '2024-01-01 00:00:00',
                'end_date' => '2024-12-31 23:59:59',
                'created_date' => '2024-01-01 00:00:00'
            ]
        ];
        
        $result = $this->module->getProductDiscount(1, $active_promotions);
        
        $this->assertNotNull($result);
        $this->assertEquals('specific_price', $result['type']);
        $this->assertEquals(40.0, $result['discount_percent']);
    }
    
    public function testIsCountdownPromotionBest()
    {
        // Test: verifica se countdown è migliore
        $active_promotions = [
            [
                'id_promotion' => 1,
                'name' => 'Test Promotion',
                'discount_percent' => 15.0,
                'start_date' => '2024-01-01 00:00:00',
                'end_date' => '2024-12-31 23:59:59',
                'created_date' => '2024-01-01 00:00:00'
            ]
        ];
        
        $is_best = $this->module->isCountdownPromotionBest(1, $active_promotions);
        $this->assertTrue($is_best);
    }
}
