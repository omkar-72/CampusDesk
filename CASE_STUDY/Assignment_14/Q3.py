# Case Study 3: Employee Salary Analysis

import pandas as pd
import statistics as stats
import matplotlib.pyplot as plt

# 1. Read salary data from CSV file
df = pd.read_csv("salary.csv")

print("Employee Salary Data:")
print(df)

# Convert salary column into a list
salary = df["Salary"].tolist()

# 2. Calculate mean, median and mode
mean_salary = stats.mean(salary)
median_salary = stats.median(salary)
mode_salary = stats.multimode(salary)

print("\nMean Salary:", mean_salary)
print("Median Salary:", median_salary)
print("Mode Salary:", mode_salary)

# 3. Calculate standard deviation and variance
standard_deviation = stats.stdev(salary)
variance = stats.variance(salary)

print("Standard Deviation:", standard_deviation)
print("Variance:", variance)

# 4. Calculate range
salary_range = max(salary) - min(salary)

print("Range:", salary_range)

# 5. Calculate quartiles and IQR
Q1 = stats.quantiles(salary, n=4)[0]
Q3 = stats.quantiles(salary, n=4)[2]

IQR = Q3 - Q1

print("\nQ1:", Q1)
print("Q3:", Q3)
print("IQR:", IQR)

# 6. Identify outliers using IQR
lower_limit = Q1 - 1.5 * IQR
upper_limit = Q3 + 1.5 * IQR

outliers = [x for x in salary if x < lower_limit or x > upper_limit]

print("\nOutliers:", outliers)

# 7. Plot salary distribution
plt.hist(salary, bins=5)
plt.title("Employee Salary Distribution")
plt.xlabel("Salary")
plt.ylabel("Frequency")
plt.show()