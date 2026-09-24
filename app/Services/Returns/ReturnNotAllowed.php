<?php

namespace App\Services\Returns;

use RuntimeException;

/**
 * A return step asked for at the wrong moment. The message is shown as is.
 */
class ReturnNotAllowed extends RuntimeException {}
