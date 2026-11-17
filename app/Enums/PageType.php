<?php

namespace App\Enums;

/**
 * What a row in `pages` is: one of the policy pages listed in the footer, or
 * the about page.
 */
enum PageType: string
{
    case Policy = 'policy';
    case About = 'about';
}
