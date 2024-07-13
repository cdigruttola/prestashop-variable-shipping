<?php
/**
 * Copyright since 2024 Carmine Di Gruttola
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    cdigruttola <c.digruttola@hotmail.it>
 * @copyright Copyright since 2007 Carmine Di Gruttola
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

declare(strict_types=1);

namespace cdigruttola\Module\VariableShipping\Entity;

use Doctrine\ORM\Mapping as ORM;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @ORM\Entity(repositoryClass="cdigruttola\Module\VariableShipping\Repository\CartVariableShippingRepository")
 *
 * @ORM\Table()
 */
class CartVariableShipping
{
    /**
     * @var int
     *
     * @ORM\Id
     *
     * @ORM\Column(name="id_cart", type="integer")
     *
     */
    private $id_cart;

    /**
     * @var float
     *
     * @ORM\Column(name="custom_price", type="float")
     */
    private $custom_price;

    public function __construct()
    {
    }

    public function getIdCart(): int
    {
        return $this->id_cart;
    }

    public function setIdCart(int $id_cart): void
    {
        $this->id_cart = $id_cart;
    }

    public function getCustomPrice(): float
    {
        return $this->custom_price;
    }

    public function setCustomPrice(float $custom_price): void
    {
        $this->custom_price = $custom_price;
    }

}
