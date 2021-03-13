SELECT branch.country, branch.state, AVG(loan.value)
FROM branch INNER JOIN loan ON branch.id = loan.branch_id
WHERE loan.is_active = true;