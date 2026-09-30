<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * The five stars drawn beside an average score.
 *
 * A product sitting at 4.7 used to be drawn with five full stars, which reads
 * as a flawless record the shop has not earned. These lock the rounding at the
 * half star.
 */
class RatingStarsTest extends TestCase
{
    /**
     * @return array{day: int, nua: int, rong: int}
     */
    private function draw(float $rating): array
    {
        $html = Blade::render(
            "@include('components.rating_stars', ['rating' => \$rating])",
            ['rating' => $rating],
        );

        return [
            'day' => substr_count($html, 'fas fa-star"'),
            'nua' => substr_count($html, 'fa-star-half-alt'),
            'rong' => substr_count($html, 'far fa-star'),
        ];
    }

    public function test_bon_phay_bay_sao_thi_sao_cuoi_chi_to_mot_nua(): void
    {
        $this->assertSame(['day' => 4, 'nua' => 1, 'rong' => 0], $this->draw(4.7));
    }

    public function test_diem_tron_thi_khong_co_sao_nua(): void
    {
        $this->assertSame(['day' => 4, 'nua' => 0, 'rong' => 1], $this->draw(4.0));
        $this->assertSame(['day' => 5, 'nua' => 0, 'rong' => 0], $this->draw(5.0));
    }

    public function test_diem_thap_khong_lam_tron_len_thanh_sao_day(): void
    {
        // 4,2 gần 4 hơn 4,5: tô nửa sao ở đây là khen quá tay.
        $this->assertSame(['day' => 4, 'nua' => 0, 'rong' => 1], $this->draw(4.2));
        $this->assertSame(['day' => 3, 'nua' => 1, 'rong' => 1], $this->draw(3.5));
    }

    public function test_chua_ai_danh_gia_thi_nam_sao_deu_rong(): void
    {
        $this->assertSame(['day' => 0, 'nua' => 0, 'rong' => 5], $this->draw(0));
    }

    public function test_luon_ve_du_nam_sao(): void
    {
        foreach ([0, 0.5, 1.4, 2.5, 3.8, 4.7, 5.0] as $diem) {
            $ve = $this->draw((float) $diem);

            $this->assertSame(
                5,
                $ve['day'] + $ve['nua'] + $ve['rong'],
                'Điểm '.$diem.' phải vẽ đúng 5 sao',
            );
        }
    }
}
