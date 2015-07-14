<?php
/*
 * Copyright 2014 Jérôme Gasperi
 *
 * Licensed under the Apache License, version 2.0 (the "License");
 * You may not use this file except in compliance with the License.
 * You may obtain a copy of the License at:
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 */

/**
 * RESTo REST router for PUT requests
 * 
 *    collections/{collection}                      |  Update {collection}
 *    collections/{collection}/{feature}            |  Update {feature}
 *    
 *    user                                          |  Modify user profile
 *    user/cart/{itemid}                            |  Modify item in user cart
 *    user/groups                                   |  Modify user groups (only admin)
 *   
 */
class RestoRoutePUT extends RestoRoute {
    
    /**
     * Constructor
     */
    public function __construct($context, $user) {
        parent::__construct($context, $user);
    }
   
    /**
     * Process HTTP PUT request
     *
     * @param array $segments
     */
    public function route($segments) {
        
        /*
         * Input data is mandatory for PUT request
         */
        $data = RestoUtil::readInputData($this->context->uploadDirectory);
        if (!is_array($data) || count($data) === 0) {
            RestoLogUtil::httpError(400);
        }

        switch($segments[0]) {
            case 'collections':
                return $this->PUT_collections($segments, $data);
            case 'user':
                return $this->PUT_user($segments, $data);
            default:
                return $this->processModuleRoute($segments, $data);
        }
        
    }
    
    /**
     * 
     * Process HTTP PUT request on collections
     * 
     *    collections/{collection}                      |  Update {collection}
     *    collections/{collection}/{feature}            |  Update {feature}
     * 
     * @param array $segments
     * @param array $data
     */
    private function PUT_collections($segments, $data) {
        
        /*
         * {collection} is mandatory and no modifier is allowed
         */
        if (!isset($segments[1]) || isset($segments[3])) {
            RestoLogUtil::httpError(404);
        }
        
        $collection = new RestoCollection($segments[1], $this->context, $this->user, array('autoload' => true));
        $featureIdentifier = isset($segments[2]) ? $segments[2] : null;
        if (isset($featureIdentifier)) {
            $feature = new RestoFeature($this->context, $this->user, array(
                'featureIdentifier' => $featureIdentifier,
                'collection' => $collection
            ));
            if (!$feature->isValid()) {
                RestoLogUtil::httpError(404);
            }
        }
        
        /*
         * Only owner of the collection can update it
         */
        if (!$this->user->hasUpdateRights($collection)) {
            RestoLogUtil::httpError(403);
        }
        
        /*
         * collections/{collection}
         */
        if (!isset($feature)) {
            
            $collection->loadFromJSON($data, true);
            
            if ($this->context->storeQuery === true) {
                $this->user->storeQuery($this->context->method, 'update', $collection->name, null, $this->context->query, $this->context->getUrl());
            }
            return RestoLogUtil::success('Collection ' . $collection->name . ' updated');
        }
        /*
         * collections/{collection}/{feature}
         */
        else {
            RestoLogUtil::httpError(501);
        }
        
    }
    
    
    /**
     * 
     * Process HTTP PUT request on users
     *
     *    user
     *    user/cart/{itemid}                            |  Modify item in user cart
     * 
     * @param array $segments
     * @param array $data
     */
    private function PUT_user($segments, $data) {
        
        /*
         * user
         */
        if (!isset($segments[1])) {
            return $this->API->updateUserProfile($this->user, $data);
        }
        
        /*
         * user/cart/{itemid}
         */
        else if ($segments[1] === 'cart' && isset($segments[2])) {
            return $this->API->updateCartItem($this->user, $segments[2], $data);
        }
        else {
            RestoLogUtil::httpError(404);
        }
        
    }
    
}
