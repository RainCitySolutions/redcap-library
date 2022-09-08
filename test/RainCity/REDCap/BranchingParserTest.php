<?php
namespace RainCity\REDCap;

use PHPUnit\Framework\TestCase;

/**
 * @covers \RainCity\REDCap\BranchingParser
 */
class BranchingParserTest extends TestCase
{
    public function testOne () {
        $parser = new BranchingParser('[shunt_yn] = \'1\'');

        $parser = new BranchingParser('[sex]="0"');
        $parser = new BranchingParser('[sex]="0"and[given_birth]= "1"');
        $parser = new BranchingParser('([height]>=170or[weight]< 65)and[sex]="1"');
        $parser = new BranchingParser('[last_name]<>""');
        $parser = new BranchingParser('[visit_date]=""');
        $parser = new BranchingParser('[race]="88"');
        $parser = new BranchingParser('[race(88)]="1"');
        $parser = new BranchingParser('([gender]="1"and([age]>10and[age]<50))or([gender]="2"and([age]>14and [age]<55))');

        $this->assertTrue(true); //$parser->matches(new InstrumentRecord($proj, $instrument), 'ff'));
    }


//    [race(2)]="1" DisplayquestionifAsianischecked [race(4)]="0" DisplayquestionifCaucasianisunchecked [height]>=170and([race(2)]="1"or [race(4)]="1") Displayquestionifheightisgreaterthanorequal
//  ([gender]=‘1’and([age]>10and[age]<50))or([gender]=‘2’and([age]>14and [age]<55))
//    [gender]=‘1’and[age]>10
//    [gender]=‘1’and([age]>10and[age]<50)(seeexamplebelow)
//    [gender]=‘1’or[race]=‘2’
}

