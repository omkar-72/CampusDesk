# Assignment No. 15
# Case Study 1: E-commerce Sales Analysis

import pandas as pd
import matplotlib.pyplot as plt

# Read data from CSV file
df = pd.read_csv("ecommerce.csv")

print("E-commerce Sales Data:")
print(df)

# 1. Bar chart - Top 10 product categories by sales revenue
category_sales = df.groupby("Category")["Sales"].sum().sort_values(ascending=False).head(10)

plt.figure(figsize=(10, 5))
category_sales.plot(kind="bar")
plt.title("Top 10 Product Categories by Sales Revenue")
plt.xlabel("Product Category")
plt.ylabel("Sales Revenue")
plt.xticks(rotation=45)
plt.tight_layout()
plt.show()


# 2. Line graph - Trend of total sales revenue over the past year
monthly_sales = df.groupby("Month")["Sales"].sum()

plt.figure(figsize=(10, 5))
monthly_sales.plot(kind="line", marker="o")
plt.title("Monthly Sales Revenue")
plt.xlabel("Month")
plt.ylabel("Sales Revenue")
plt.grid()
plt.tight_layout()
plt.show()


# 3. Scatter plot - Relationship between units sold and customer age
plt.figure(figsize=(8, 5))
plt.scatter(df["Age"], df["Units_Sold"])
plt.title("Age vs Units Sold")
plt.xlabel("Customer Age")
plt.ylabel("Units Sold")
plt.grid()
plt.show()


# 4. Pie chart - Proportion of sales revenue from customer segments
segment_sales = df.groupby("Customer_Segment")["Sales"].sum()

plt.figure(figsize=(7, 7))
plt.pie(segment_sales, labels=segment_sales.index, autopct="%1.1f%%")
plt.title("Sales Revenue by Customer Segment")
plt.show()