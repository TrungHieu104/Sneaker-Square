<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('contact')->insert([
            [
                'contact_email'=>'cskh@localhost',
                'contact_phone'=>'0369469525',
                'contact_address'=>'99 Nguyễn Du, Bến Thành, Quận 1, TP.Hồ Chí Minh',
                'map_link'=>'<iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2770.755005277463!2d106.62671884674846!3d10.85415320776005!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31752a20d8555e69%3A0x743b1e9558fb89e0!2sQTSC%209%20Building!5e0!3m2!1svi!2s!4v1693803926332!5m2!1svi!2s"
                    width="100%" height="450" class="border-0 rounded" allowfullscreen="" loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>',
                'fanpage_link'=>'<div class="fb-page w-100" data-href="#" 
                    data-tabs="" data-width="" data-height="250" data-small-header="false" 
                    data-adapt-container-width="true" data-hide-cover="false" 
                    data-show-facepile="true">
                    <blockquote cite="#" 
                    class="fb-xfbml-parse-ignore">
                    <a href="#">Sneaker Square</a>
                    </blockquote>
                </div>',
                'tawk_link' => '<script type=\"text/javascript\"> var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
                    (function(){ var s1=document.createElement(\"script\"),s0=document.getElementsByTagName(\"script\")[0];
                    s1.async=true; 
                    s1.src=\'https://embed.tawk.to/6540bf6ba84dd54dc4870a33/1he2enfgb\'; 
                    s1.charset=\'UTF-8\'; s1.setAttribute(\'crossorigin\',\'*\'); 
                    s0.parentNode.insertBefore(s1,s0); 
                    })(); 
                </script>',
                'contact_hidden' => 1,
                'contact_created_by' => 'Thuận',
            ]
        ]);
    }
}
