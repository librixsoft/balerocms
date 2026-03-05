<?php

declare(strict_types=1);

namespace Tests\Framework\Core;

use Framework\Core\ViewModel;
use PHPUnit\Framework\TestCase;

final class ViewModelTest extends TestCase
{
    public function testAddAddAllAllAndClear(): void
    {
        $vm = new ViewModel();
        $vm->add('a', 1);
        $vm->addAll(['b' => 2, 'c' => 3]);

        $this->assertSame(['a' => 1, 'b' => 2, 'c' => 3], $vm->all());

        $vm->clear();
        $this->assertSame([], $vm->all());
    }
}
