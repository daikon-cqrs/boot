<?php
namespace Psalm\Tests;

use Psalm\Context;

class BadFormatTest extends TestCase
{
    /**
     * @expectedException Psalm\Exception\CodeException
     * @expectedExceptionMessage  ParseError - somefile.php:9
     * @return void
     */
    public function testMissingSemicolon()
    {
        $this->addFile(
            'somefile.php',
            '<?php
                class A {
                    /** @var int|null */
                    protected $hello;

                    /** @return void */
                    function foo() {
                        $this->hello = 5
                    }
                }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @expectedException Psalm\Exception\CodeException
     * @expectedExceptionMessage  ParseError - somefile.php:5
     * @return void
     */
    public function testTypingReturnType()
    {
        $this->addFile(
            'somefile.php',
            '<?php
                class A {
                    /** @return void */
                    protected function _getCollaborators(User $user, User $cur_user = null) :
                    {
                        return $a;
                    }
                }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }
}
