<?php

namespace AnourValar\EloquentRequest\Tests;

class SearchServiceTest extends AbstractSuite
{
    /**
     * @return void
     */
    public function test_typo()
    {
        $this->assertNull(\EloquentRequestSearch::typo(null, 'en', 'ru'));
        $this->assertSame('123', \EloquentRequestSearch::typo('123', 'en', 'ru'));


        $this->assertSame('привет', \EloquentRequestSearch::typo('привет', 'en', 'ru'));
        $this->assertSame('ПРИВЕТ', \EloquentRequestSearch::typo('ПРИВЕТ', 'en', 'ru'));
        $this->assertSame('привет', \EloquentRequestSearch::typo('ghbdtn', 'en', 'ru'));
        $this->assertSame('ПРИВЕТ', \EloquentRequestSearch::typo('GHBDTN', 'en', 'ru'));

        $this->assertSame('ейцукенгшщзхъ', \EloquentRequestSearch::typo('`qwertyuiop[]', 'en', 'ru'));
        $this->assertSame('фывапролджэ', \EloquentRequestSearch::typo('asdfghjkl;\'', 'en', 'ru'));
        $this->assertSame('ячсмитьбю', \EloquentRequestSearch::typo('zxcvbnm,.', 'en', 'ru'));

        $this->assertSame('ЕЙЦУКЕНГШЩЗХЪ', \EloquentRequestSearch::typo('~QWERTYUIOP{}', 'en', 'ru'));
        $this->assertSame('ФЫВАПРОЛДЖЭ', \EloquentRequestSearch::typo('ASDFGHJKL:"', 'en', 'ru'));
        $this->assertSame('ЯЧСМИТЬБЮ', \EloquentRequestSearch::typo('ZXCVBNM<>', 'en', 'ru'));

        $this->assertSame('~', \EloquentRequestSearch::typo('~', 'en', 'ru'));
        $this->assertSame('ФЕ', \EloquentRequestSearch::typo('A~', 'en', 'ru'));


        $this->assertSame('hello', \EloquentRequestSearch::typo('hello', 'ru', 'en'));
        $this->assertSame('HELLO', \EloquentRequestSearch::typo('HELLO', 'ru', 'en'));
        $this->assertSame('hello', \EloquentRequestSearch::typo('руддщ', 'ru', 'en'));
        $this->assertSame('HELLO', \EloquentRequestSearch::typo('РУДДЩ', 'ru', 'en'));

        $this->assertSame('tqwertyuiop[]', \EloquentRequestSearch::typo('ейцукенгшщзхъ', 'ru', 'en'));
        $this->assertSame('asdfghjkl;\'', \EloquentRequestSearch::typo('фывапролджэ', 'ru', 'en'));
        $this->assertSame('zxcvbnm,.', \EloquentRequestSearch::typo('ячсмитьбю', 'ru', 'en'));

        $this->assertSame('TQWERTYUIOP{}', \EloquentRequestSearch::typo('ЕЙЦУКЕНГШЩЗХЪ', 'ru', 'en'));
        $this->assertSame('ASDFGHJKL:"', \EloquentRequestSearch::typo('ФЫВАПРОЛДЖЭ', 'ru', 'en'));
        $this->assertSame('ZXCVBNM<>', \EloquentRequestSearch::typo('ЯЧСМИТЬБЮ', 'ru', 'en'));
    }

    /**
     * @return void
     */
    public function test_similar()
    {
        $this->assertNull(\EloquentRequestSearch::similar(null, 'en', 'ru'));


        $this->assertSame('`qwеrтуuiор[]', \EloquentRequestSearch::similar('`qwertyuiop[]', 'en', 'ru'));
        $this->assertSame('аsdfgнjкl;\'', \EloquentRequestSearch::similar('asdfghjkl;\'', 'en', 'ru'));
        $this->assertSame('zхсvвnм,.', \EloquentRequestSearch::similar('zxcvbnm,.', 'en', 'ru'));

        $this->assertSame('~QWЕRТУUIОР{}', \EloquentRequestSearch::similar('~QWERTYUIOP{}', 'en', 'ru'));
        $this->assertSame('АSDFGНJКL:"', \EloquentRequestSearch::similar('ASDFGHJKL:"', 'en', 'ru'));
        $this->assertSame('ZХСVВNМ<>', \EloquentRequestSearch::similar('ZXCVBNM<>', 'en', 'ru'));


        $this->assertSame('eйцykehгшщзxъ', \EloquentRequestSearch::similar('ейцукенгшщзхъ', 'ru', 'en'));
        $this->assertSame('фыbaпpoлджэ', \EloquentRequestSearch::similar('фывапролджэ', 'ru', 'en'));
        $this->assertSame('ячcmиtьбю', \EloquentRequestSearch::similar('ячсмитьбю', 'ru', 'en'));

        $this->assertSame('EЙЦYKEHГШЩЗXЪ', \EloquentRequestSearch::similar('ЕЙЦУКЕНГШЩЗХЪ', 'ru', 'en'));
        $this->assertSame('ФЫBAПPOЛДЖЭ', \EloquentRequestSearch::similar('ФЫВАПРОЛДЖЭ', 'ru', 'en'));
        $this->assertSame('ЯЧCMИTЬБЮ', \EloquentRequestSearch::similar('ЯЧСМИТЬБЮ', 'ru', 'en'));
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
