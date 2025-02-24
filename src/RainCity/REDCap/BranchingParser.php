<?php
declare(strict_types = 1);
namespace RainCity\REDCap;

class BranchingParser
{
    public function __construct(string $branchingStr)
    {
        $expression = strtolower($branchingStr);
        $expression = str_replace("\n", ' ', $expression);
        $expression = preg_replace('/ *or */', ' | ', $expression);
        $expression = preg_replace('/ *and */', ' & ', $expression);

        $expression = preg_replace('/([a-z,0-9,_]+)\\((?<=\\()(.*?)(?=\\))\\)/', '$1___$2', $expression);

        $expression = preg_replace('/([[]|[]])/', '', $expression);
        $expression = preg_replace('/ *= */', ' == ', $expression);
        $expression = preg_replace('/[!] [=]/', ' !', $expression);
        $expression = preg_replace('/[<] [=]/', ' <', $expression);
        $expression = preg_replace('/[>] [=]/', ' >', $expression);
        $expression = preg_replace('/ *[<][>] */', ' != ', $expression);
        $expression = preg_replace('/ *>/', ' >', $expression);
        $expression = preg_replace('/ *</', ' <', $expression);

//        lapply(l, function(x) ifelse(x=="", NA, parse(text=x)))

//         $matches = array();

//         if (preg_match_all('([.*]\b?(=!).*)', $branching, $matches))
//         {

//         }
    }

    public function matches(InstrumentRecord $instRcd, ?string $event = null): bool
    {
        $matches = false;

        // parse the branching
        $field = '';
        $branchValue = '';

        $fieldValue = $instRcd->getFieldValue($field, $event);

        if ($fieldValue == $branchValue) {
            $matches = true;
        }

        return $matches;
    }
}

/*
#' @name parseBranchingLogic
#' @export parseBranchingLogic
#'
#' @title Parse Branching Logic
#' @description Branching logic from the REDCap Data Dictionary is parsed into
#'   R Code and returned as expressions.  These can be evaluated if desired
#'   and allow the user to determine if missing values are truly missing or
#'   not required because the branching logic prevented the variable from being
#'   presented.
#'
#' @param l A vector of REDCap branching logic statements.  These are usually
#'   passed as the vector \code{meta_data$branching_logic}.
#'
#' @details For a study, I was asked to identify which subjects had missing
#'   values so that remaining data could be collected.  The initial pass of
#'   \code{is.na} produced a lot of subjects missing values where there was no
#'   need to collect data because they did not qualify for some variables in
#'   the branching logic.  Parsing the logic allowed me to determine which
#'   values we expected to be missing and narrow the search to just those
#'   subjects with legitimately missing values.
#'
#' @return Returns a list of unevaluated expressions.
#'
#' @author Benjamin Nutter
#'

parseBranchingLogic <- function(l){
  l <- tolower(l)
  l <- gsub("\\n", " ", l)
  l <- gsub(" or ", " | ", l)
  l <- gsub(" and ", " & ", l)
  l <- gsub("([a-z,0-9,_])\\((?<=\\()(.*?)(?=\\))\\)",
            "\\1___\\2",
            l,
            perl = TRUE)
  l <- gsub("([[]|[]])", "", l)
  l <- gsub("[=]", " == ", l)
  l <- gsub("[!] [=]", " !", l)
  l <- gsub("[<] [=]", " <", l)
  l <- gsub("[>] [=]", " >", l)
  l <- gsub("[<][>]", "!=", l)
  lapply(l, function(x) ifelse(x=="", NA, parse(text=x)))
}

*/