<?php

namespace App\Enums;

/**
 * The values `order.order_status` already holds, given names.
 *
 * The column is declared boolean but has always carried five states — the
 * order exports and the admin filters read 0, 1, 2, 3 and 10 as five
 * different labels. Naming them here keeps
 * new code from spreading bare numbers any further.
 */
enum OrderStatus: int
{
    case New = 0;
    case Confirmed = 1;
    case Cancelled = 2;
    case Returned = 3;
    case Completed = 10;
}
