<?php

namespace belamov\EpubParser;


class EpubTest extends \PHPUnit_Framework_TestCase
{
    public function test_it_unzips_book()
    {
        require "../lib/epub.php";
        require "../lib/meta.php";
        $this->parser = new Epub(__DIR__ . "/book.epub", __DIR__ . "/working_dir/", __DIR__ . "/");
        $this->assertDirectoryExists(__DIR__ . "/working_dir/");
        $this->assertDirectoryExists(__DIR__ . "/images");
    }

    public function test_it_fetches_meta_data()
    {
        $parser = new Epub(__DIR__ . "/book.epub", __DIR__ . "/working_dir/", __DIR__ . "/");
        $this->assertEquals("Все секреты покупки квартиры в новостройке. Опыт успешного собственника", $parser->getTitle());
        $this->assertEquals("Все секреты покупки квартиры в новостройке. Опыт успешного собственника", $parser->getTitle());
        $this->assertEquals("Екатерина В. Ованова", $parser->getAuthor());
        $this->assertEquals("В этой книге есть все, что нужно знать жителю России о приобретении квартиры в новостройке. В ней рассмотрены основные вопросы, с которыми столкнется будущий собственник в процессе выбора квартиры и оформления сделки – правовые, бытовые, психологические. Приведены примеры рисков, связанных с этим важным шагом – особенности страхования, применение Федерального закона 214-ФЗ, правила приема квартиры у застройщика. Автор раскрывает множество секретов, которые может знать только практик, много лет изучающий тему на собственном опыте: как сделать бюджетный ремонт, который увеличит стоимость квартиры в 1,5 раза, на что обратить внимание, если вы решились на перепланировку, и каким образом вдвое увеличить выгоды при аренде квартиры. Эта книга – ваша инвестиция в себя, как собственника и инвестора, в ваше настоящее и будущее. ", $parser->getDescription());
        $this->assertFileEquals($parser->getCoverPath(), __DIR__ . "/files/cover.jpg");
    }

    public function test_it_gets_contents()
    {
        $epub = new Epub(__DIR__ . "/book.epub", __DIR__ . "/working_dir/", __DIR__ . "/");
        foreach ($epub as $page){
            $this->assertNotEmpty($page['text']);
        }
    }
     public function test_it_cleans_working_dir(){
         $epub = new Epub(__DIR__ . "/book.epub", __DIR__ . "/working_dir/", __DIR__ . "/");
         $epub->cleanWorkingDir();
         $this->assertDirectoryNotExists(__DIR__ . "/working_dir/*");
     }
    //TODO: tests for links substitution; for content matching
}
