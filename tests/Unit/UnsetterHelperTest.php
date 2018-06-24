<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

/*
 * Unsets properties on (nested) objects.
 *
 * @param  object $object
 * @param  string $property
 *
 * @return object|null
 */
function unsetter(object $object, string $property): ?object
{
    $props = collect(explode('->', $property));
    $obj = collect($object);
    $unset = $props->pop();
    if($props->count() == 0){
        $obj = (object) collect($object)->forget($unset)->toArray();
        
        if(empty((array) $obj)){
            return null;
        }else{
            return $obj;    
        }
        
    }else{
        $obj = $obj->map(function($value, $key) use($unset) {
            if(is_object($value)){
                return (object) collect($value)->map(function($item, $key) use($unset) {
                    if(is_object($item)) {
                        return (object) collect($item)->filter(function($item, $key) use($unset) {
                            return $key != $unset;
                        })->toArray();
                    }
                })->toArray();
            }else {            
                return $value;
            }
        });
        
        $obj = $obj->filter(function($item, $key) {
            if(is_object($item)){   
                if(!empty((array) $item)){
                    return collect($item)->filter(function($item, $key) {
                        if(empty((array) $item)){
                            return false;
                        }else {
                            return $item;
                        }
                    })->isNotEmpty();
                }
            }
            return $item;
        });
        
        return (object) $obj->toArray();
    }
    
}

class UnsetterHelperTest extends TestCase
{
    public function testUnsettingRootProperty()
    {
        $object = (object) [
            'one' => 'foo',
            'two' => 'bar',
        ];

        $result = (object) [
            'one' => 'foo',
        ];

        $this->assertEquals(
            unsetter($object, 'two'), $result
        );
    }

    public function testEmptyObjectReturnsNull()
    {
        $object = (object) [
            'foo' => 'bar',
        ];

        $this->assertNull(
            unsetter($object, 'foo')
        );
    }

    public function testUnsettingNestedProperty()
    {
        $object = (object) [
            'one' => (object) [
                'two' => (object) [
                    'foobar' => 'bar',
                    'foobaz' => 'baz',
                ],
            ],
        ];

        $result = (object) [
            'one' => (object) [
                'two' => (object) [
                    'foobar' => 'bar',
                ],
            ],
        ];

        $this->assertEquals(
            unsetter($object, 'one->two->foobaz'), $result
        );
    }

    public function testUnsettingNestedPropertyIsRemovedWhenEmpty()
    {
        $object = (object) [
            'foo' => 'bar',
            'one' => (object) [
                'two' => (object) [
                    'three' => 'foo',
                ],
            ],
        ];

        $result = (object) [
            'foo' => 'bar',
        ];

        $this->assertEquals(
            unsetter($object, 'one->two->three'), $result
        );
    }

    public function testUnsettingNestedPropertyWithTheSameName()
    {
        $object = (object) [
            'foo' => (object) [
                'foo' => (object) [
                    'foo' => 'bar',
                    'bar' => 'baz',
                ],
            ],
        ];

        $result = (object) [
            'foo' => (object) [
                'foo' => (object) [
                    'bar' => 'baz',
                ],
            ],
        ];

        $this->assertEquals(
            unsetter($object, 'foo->foo->foo'), $result
        );
    }
}