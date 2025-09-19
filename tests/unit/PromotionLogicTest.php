<?php

use PHPUnit\Framework\TestCase;

class PromotionLogicTest extends TestCase
{
    public function testPromotionComparisonLogic()
    {
        // Simula la logica di confronto delle promozioni
        $active_promotions = [
            [
                'id_promotion' => 1,
                'name' => 'Countdown 15%',
                'discount_percent' => 15.0,
                'start_date' => '2024-01-01 00:00:00',
                'end_date' => '2024-12-31 23:59:59'
            ]
        ];
        
        $specific_prices = [
            [
                'id_specific_price' => 1,
                'reduction_type' => 'percentage',
                'reduction' => 0.40, // 40%
                'from' => '2024-01-01 00:00:00',
                'to' => '2024-12-31 23:59:59',
                'id_cart' => 0
            ]
        ];
        
        $base_price = 100.0;
        $best_discount = null;
        $best_discount_percent = 0;
        
        // 1. Controlla promozioni countdown
        foreach ($active_promotions as $promotion) {
            $discount_percent = (float)$promotion['discount_percent'];
            if ($discount_percent > $best_discount_percent) {
                $best_discount_percent = $discount_percent;
                $best_discount = [
                    'type' => 'countdown',
                    'discount_percent' => $discount_percent
                ];
            }
        }
        
        // 2. Controlla SpecificPrice
        foreach ($specific_prices as $sp) {
            $discount_percent = (float)$sp['reduction'] * 100;
            if ($discount_percent > $best_discount_percent) {
                $best_discount_percent = $discount_percent;
                $best_discount = [
                    'type' => 'specific_price',
                    'discount_percent' => $discount_percent
                ];
            }
        }
        
        // Verifica che SpecificPrice (40%) sia migliore di Countdown (15%)
        $this->assertNotNull($best_discount);
        $this->assertEquals('specific_price', $best_discount['type']);
        $this->assertEquals(40.0, $best_discount['discount_percent']);
        
        // Verifica che countdown NON sia la migliore
        $is_countdown_best = $best_discount['type'] === 'countdown';
        $this->assertFalse($is_countdown_best);
    }
    
    public function testCountdownIsBestWhenHigher()
    {
        // Test: countdown 25% vs specific_price 20%
        $active_promotions = [
            [
                'id_promotion' => 1,
                'name' => 'Countdown 25%',
                'discount_percent' => 25.0,
                'start_date' => '2024-01-01 00:00:00',
                'end_date' => '2024-12-31 23:59:59'
            ]
        ];
        
        $specific_prices = [
            [
                'id_specific_price' => 1,
                'reduction_type' => 'percentage',
                'reduction' => 0.20, // 20%
                'from' => '2024-01-01 00:00:00',
                'to' => '2024-12-31 23:59:59',
                'id_cart' => 0
            ]
        ];
        
        $best_discount = null;
        $best_discount_percent = 0;
        
        // Controlla promozioni countdown
        foreach ($active_promotions as $promotion) {
            $discount_percent = (float)$promotion['discount_percent'];
            if ($discount_percent > $best_discount_percent) {
                $best_discount_percent = $discount_percent;
                $best_discount = [
                    'type' => 'countdown',
                    'discount_percent' => $discount_percent
                ];
            }
        }
        
        // Controlla SpecificPrice
        foreach ($specific_prices as $sp) {
            $discount_percent = (float)$sp['reduction'] * 100;
            if ($discount_percent > $best_discount_percent) {
                $best_discount_percent = $discount_percent;
                $best_discount = [
                    'type' => 'specific_price',
                    'discount_percent' => $discount_percent
                ];
            }
        }
        
        // Verifica che Countdown (25%) sia migliore di SpecificPrice (20%)
        $this->assertNotNull($best_discount);
        $this->assertEquals('countdown', $best_discount['type']);
        $this->assertEquals(25.0, $best_discount['discount_percent']);
        
        // Verifica che countdown SIA la migliore
        $is_countdown_best = $best_discount['type'] === 'countdown';
        $this->assertTrue($is_countdown_best);
    }
    
    public function testDisplayLogic()
    {
        // Test: logica di visualizzazione
        $test_cases = [
            [
                'best_discount' => ['type' => 'countdown', 'discount_percent' => 15.0],
                'should_show_panels' => true,
                'should_apply_cart' => true,
                'description' => 'Countdown è migliore'
            ],
            [
                'best_discount' => ['type' => 'specific_price', 'discount_percent' => 40.0],
                'should_show_panels' => false,
                'should_apply_cart' => false,
                'description' => 'SpecificPrice è migliore'
            ],
            [
                'best_discount' => null,
                'should_show_panels' => false,
                'should_apply_cart' => false,
                'description' => 'Nessuna promozione'
            ]
        ];
        
        foreach ($test_cases as $case) {
            $should_show_panels = $case['best_discount'] && $case['best_discount']['type'] === 'countdown';
            $should_apply_cart = $case['best_discount'] && $case['best_discount']['type'] === 'countdown';
            
            $this->assertEquals($case['should_show_panels'], $should_show_panels, 
                "Panel display failed for: {$case['description']}");
            $this->assertEquals($case['should_apply_cart'], $should_apply_cart, 
                "Cart application failed for: {$case['description']}");
        }
    }
}
