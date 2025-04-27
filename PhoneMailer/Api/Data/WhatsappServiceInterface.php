<?php

namespace MagoArab\PhoneMailer\Api\Data;

/**
 * Interface for WhatsApp service data
 * @api
 */
interface WhatsappServiceInterface
{
    /**
     * Get WhatsApp message ID
     *
     * @return string|null
     */
    public function getMessageId();

    /**
     * Set WhatsApp message ID
     *
     * @param string $messageId
     * @return $this
     */
    public function setMessageId($messageId);

    /**
     * Get recipient phone number
     *
     * @return string|null
     */
    public function getRecipient();

    /**
     * Set recipient phone number
     *
     * @param string $recipient
     * @return $this
     */
    public function setRecipient($recipient);

    /**
     * Get message content
     *
     * @return string|null
     */
    public function getContent();

    /**
     * Set message content
     *
     * @param string $content
     * @return $this
     */
    public function setContent($content);

    /**
     * Get status
     *
     * @return string|null
     */
    public function getStatus();

    /**
     * Set status
     *
     * @param string $status
     * @return $this
     */
    public function setStatus($status);

    /**
     * Get created at timestamp
     *
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Set created at timestamp
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt);
}