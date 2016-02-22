<?php
/**
 * Copyright 2016 Klarna AB.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace Klarna\XMLRPC\Exception;

/**
 * Exception for invalid Email.
 */
class UnsupportedMarketException extends KlarnaException
{
    /**
     * Constructor.
     *
     * @param string|array $countries allowed countries
     */
    public function __construct($countries)
    {
        if (is_array($countries)) {
            $countries = implode(', ', $countries);
        }
        parent::__construct(
            "This method is only available for customers from: {$countries}",
            50025
        );
    }
}