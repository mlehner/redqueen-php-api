<?php

namespace BLInc\Validator\Constraints;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UniqueValidator extends ConstraintValidator {
    /**
     * @var Connection
     */
    protected $db;

    public function __construct(Connection $db) {
        $this->db = $db;
    }

    public function validate($value, Constraint $constraint) {
        if (!$constraint instanceof Unique) {
            throw new UnexpectedTypeException($constraint, Unique::class);
        }

        /**
         * @var $stmt Statement
         */
        $stmt = $this->db->createQueryBuilder()
            ->select('t.id')
            ->from($constraint->table, 't')
            ->where(sprintf('t.%s = ?', $constraint->column))
            ->setParameter(0, $value)
            ->execute();

        if ($stmt->rowCount()) {
            $this->context->addViolation($constraint->message, array(
                '{{ value }}' => $value
            ));
        }
    }
}
