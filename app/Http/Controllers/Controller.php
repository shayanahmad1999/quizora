<?php
declare(strict_types=1);
namespace App\Http\Controllers;
use App\Support\PageResponse;
abstract class Controller
{
    public function __construct(protected readonly PageResponse $pages) {}
}
