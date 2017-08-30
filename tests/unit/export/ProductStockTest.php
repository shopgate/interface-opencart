<?php
/**
 * Copyright Shopgate Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @author    Shopgate Inc, 804 Congress Ave, Austin, Texas 78701 <interfaces@shopgate.com>
 * @copyright Shopgate Inc
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */

class ExampleTest extends \PHPUnit_Framework_TestCase
{
    /** @var ProductStock */
    private $subjectUnderTest;

    /** @var ShopgateConfigOpencart | \PHPUnit_Framework_MockObject_MockObject */
    private $shopgateOpenCartConfigMock;

    public function setUp()
    {
        $this->shopgateOpenCartConfigMock = $this->getMockBuilder('ShopgateConfigOpencart')->getMock();
        $this->subjectUnderTest           = new ProductStock($this->shopgateOpenCartConfigMock);
    }

    /**
     * @param bool  $expectedResult
     * @param mixed $configurationSubstractStock
     * @param mixed $productSubstractStock
     *
     * @dataProvider provideConfigurationCombinationForSubstractStock
     */
    public function testShouldProductStockBeReduced(
        $expectedResult,
        $configurationSubstractStock,
        $productSubstractStock
    ) {
        $databaseMock = $this->getMockBuilder('ShopgateOpencartDatabase')
            ->disableOriginalConstructor()
            ->getMock();
        $databaseMock->method('getConfigStockSubtract')->willReturn(
            $configurationSubstractStock
        );
        $this->shopgateOpenCartConfigMock->method('getOpencartDatabase')->willReturn($databaseMock);

        $this->assertEquals(
            $expectedResult,
            $this->subjectUnderTest->shouldProductStockBeReduced(
                $productSubstractStock
            )
        );
    }

    /**
     * @return array
     */
    public function provideConfigurationCombinationForSubstractStock()
    {
        return array(
            'OpenCart 1.3.0' => array(
                false,
                '',
                1,
            ),
        );
    }
}
