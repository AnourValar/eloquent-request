<?php

namespace AnourValar\EloquentRequest\Tests;

class SearchServiceTest extends \Orchestra\Testbench\TestCase
{
    /**
     * @see \Orchestra\Testbench\TestCase
     */
    protected function getPackageProviders($app)
    {
        return [\AnourValar\EloquentRequest\Providers\EloquentRequestServiceProvider::class];
    }

    /**
     * @return void
     */
    public function test_generateFulltext()
    {
        $this->assertNull($this->fulltext(null));

        $this->assertSame('', $this->fulltext('`~'));
        $this->assertSame('еев', $this->fulltext('`~d'));

        $this->assertSame('йцукенгшщзхъйцукенгшщзхъ', $this->fulltext('qwertyuiop[]QWERTYUIOP{}'));
        $this->assertSame('фывапролджэфывапролджэ', $this->fulltext('asdfghjkl;\'ASDFGHJKL:"'));
        $this->assertSame('ячсмитьбюячсмитьбю', $this->fulltext('zxcvbnm,.ZXCVBNM<>'));

        $this->assertSame('икфмщ', $this->fulltext('Браво'));
        $this->assertSame('икфмщ', $this->fulltext('браво'));
        $this->assertSame('икфмщ', $this->fulltext(',hfdj'));
        $this->assertSame('икфмщ', $this->fulltext('Bravo'));
        $this->assertSame('икфмщ', $this->fulltext('bravo'));
        $this->assertSame('икфмщ', $this->fulltext('икфмщ'));

        $this->assertSame('один два', $this->fulltext('Один/Два'));
        $this->assertSame('один два', $this->fulltext('Один / два'));
        $this->assertSame('один два', $this->fulltext(' Один  Два '));
        $this->assertSame('елка ежик', $this->fulltext('Ёлка ёжик'));
        $this->assertSame('жар птица', $this->fulltext('Жар-птица'));

        $this->assertSame('шзрщту1', $this->fulltext('iphone1'));
        $this->assertSame('шзрщту 1', $this->fulltext('iphone 1'));
        $this->assertSame('шзрщту 1', $this->fulltext('iphone_1'));
        $this->assertSame('шзрщту 1', $this->fulltext('IPHONE-1'));
        $this->assertSame('1шзрщту', $this->fulltext('1iphone'));
        $this->assertSame('1 шзрщту', $this->fulltext('1 iphone'));
        $this->assertSame('1 шзрщту', $this->fulltext('1_iphone'));
        $this->assertSame('1 шзрщту', $this->fulltext('1-IPHONE'));

        $this->assertSame('шзрщту 1', $this->fulltext('IPHONE@-@1'));
        $this->assertSame('шзрщту 1', $this->fulltext('IPHONE @@ - @@ 1'));
    }

    /**
     * @param mixed $phrase
     * @return mixed
     */
    private function fulltext($phrase)
    {
        return (new \AnourValar\EloquentRequest\SearchService())->generateFulltext($phrase, ['from' => 'en', 'to' => 'ru'], ['браво' => 'икфмщ']);
    }
}
