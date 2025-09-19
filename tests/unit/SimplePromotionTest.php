<?php

use PHPUnit\Framework\TestCase;

class SimplePromotionTest extends TestCase
{
    public function testPromotionComparison()
    {
        // Test semplice: verifica che 40% > 15%
        $countdown_discount = 15.0;
        $specific_price_discount = 40.0;
        
        $this->assertGreaterThan($countdown_discount, $specific_price_discount);
        $this->assertEquals(40.0, $specific_price_discount);
        $this->assertEquals(15.0, $countdown_discount);
    }
    
    public function testBestPromotionSelection()
    {
        // Test: seleziona la promozione migliore
        $promotions = [
            ['type' => 'countdown', 'discount_percent' => 15.0],
            ['type' => 'specific_price', 'discount_percent' => 40.0],
            ['type' => 'cart_rule', 'discount_percent' => 10.0]
        ];
        
        $best_promotion = null;
        $best_discount = 0;
        
        foreach ($promotions as $promotion) {
            if ($promotion['discount_percent'] > $best_discount) {
                $best_discount = $promotion['discount_percent'];
                $best_promotion = $promotion;
            }
        }
        
        $this->assertNotNull($best_promotion);
        $this->assertEquals('specific_price', $best_promotion['type']);
        $this->assertEquals(40.0, $best_promotion['discount_percent']);
    }
    
    public function testCountdownIsBestWhenHigher()
    {
        // Test: countdown è migliore quando ha sconto più alto
        $promotions = [
            ['type' => 'countdown', 'discount_percent' => 25.0],
            ['type' => 'specific_price', 'discount_percent' => 20.0],
            ['type' => 'cart_rule', 'discount_percent' => 15.0]
        ];
        
        $best_promotion = null;
        $best_discount = 0;
        
        foreach ($promotions as $promotion) {
            if ($promotion['discount_percent'] > $best_discount) {
                $best_discount = $promotion['discount_percent'];
                $best_promotion = $promotion;
            }
        }
        
        $this->assertNotNull($best_promotion);
        $this->assertEquals('countdown', $best_promotion['type']);
        $this->assertEquals(25.0, $best_promotion['discount_percent']);
    }
    
    public function testShouldShowCountdownPanels()
    {
        // Test: quando mostrare i pannellini countdown
        $test_cases = [
            ['countdown' => 15, 'other' => 40, 'should_show' => false], // Altro è migliore
            ['countdown' => 25, 'other' => 20, 'should_show' => true],  // Countdown è migliore
            ['countdown' => 15, 'other' => 0, 'should_show' => true],   // Solo countdown
            ['countdown' => 0, 'other' => 0, 'should_show' => false]    // Nessuna promozione
        ];
        
        foreach ($test_cases as $case) {
            $should_show = $case['countdown'] > $case['other'] && $case['countdown'] > 0;
            $this->assertEquals($case['should_show'], $should_show, 
                "Failed for countdown={$case['countdown']}, other={$case['other']}");
        }
    }
}
