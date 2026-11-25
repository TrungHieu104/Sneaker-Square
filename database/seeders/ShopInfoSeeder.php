<?php

namespace Database\Seeders;

use App\Services\ShopSettings;
use Illuminate\Database\Seeder;

class ShopInfoSeeder extends Seeder
{
    /**
     * The shop's contact details, kept as settings the admin edits on the
     * system configuration page.
     */
    public function run(): void
    {
        app(ShopSettings::class)->setShopInfo([
            'email' => 'cskh@localhost',
            'phone' => '0369469525',
            'address' => '99 Nguyễn Du, Bến Thành, Quận 1, TP.Hồ Chí Minh',
            'map_embed' => '<iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2770.755005277463!2d106.62671884674846!3d10.85415320776005!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31752a20d8555e69%3A0x743b1e9558fb89e0!2sQTSC%209%20Building!5e0!3m2!1svi!2s!4v1693803926332!5m2!1svi!2s"
                    width="100%" height="450" class="border-0 rounded" allowfullscreen="" loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>',
            'fanpage_embed' => '<div class="fb-page w-100" data-href="#" 
                    data-tabs="" data-width="" data-height="250" data-small-header="false" 
                    data-adapt-container-width="true" data-hide-cover="false" 
                    data-show-facepile="true">
                    <blockquote cite="#" 
                    class="fb-xfbml-parse-ignore">
                    <a href="#">Sneaker Square</a>
                    </blockquote>
                </div>',
        ]);
    }
}
