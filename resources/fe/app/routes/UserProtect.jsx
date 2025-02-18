import React from "react";
import { LOCAL_EMAIL, ROLE } from "../../settings/localVar";
import { Navigate } from "react-router-dom";

// higher order component: HOC

// nếu để string trực tiếp trong getItem thì nó là error chung magic number nên phải sử dụng LOCAL_EMAIL

export default function UserProtect({ children }) {
  const email = localStorage.getItem(LOCAL_EMAIL);
  const role = localStorage.getItem(ROLE);
  if (!email && role !== "admin") {
    return (
      <>
        <Navigate to={"/login"} />
      </>
    );
  }
  return <>{children}</>;
}
